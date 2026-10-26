<?php
/**
 * Part of grauphel
 *
 * PHP version 5
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link      http://cweiske.de/grauphel.htm
 */
namespace OCA\Grauphel\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http;
use \OCP\AppFramework\Http\Attribute\NoAdminRequired;
use \OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use \OCP\AppFramework\Http\Attribute\PublicPage;
use \OCP\AppFramework\Http\ContentSecurityPolicy;
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IURLGenerator;
use \OCP\IUserSession;

use \OCA\Grauphel\Auth\Client;
use \OCA\Grauphel\Auth\Token;
use \OCA\Grauphel\Auth\OAuth;
use \OCA\Grauphel\Response\ErrorResponse;
use \OCA\Grauphel\Response\FormResponse;
use \OCA\Grauphel\Auth\OAuthException;
use \OCA\Grauphel\Storage\TokenStorage;
use \OCA\Grauphel\Tools\UrlHelper;

/**
 * OAuth handling
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class OauthController extends Controller
{
    protected $user;
    protected IURLGenerator $urlGen;
    protected TokenStorage $tokens;
    protected OAuth $oauth;

    /**
     * constructor of the controller
     *
     * @param string   $appName Name of the app
     * @param IRequest $request Instance of the request
     */
    public function __construct(
        $appName, IRequest $request, IUserSession $userSession,
        IURLGenerator $urlGen, TokenStorage $tokens, OAuth $oauth
    ) {
        parent::__construct($appName, $request);
        $this->user   = $userSession->getUser();
        $this->urlGen = $urlGen;
        $this->tokens = $tokens;
        $this->oauth  = $oauth;
    }

    /**
     * Handle out an access token after verifying the verification token
     * OAuth step 3 of 3
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function accessToken()
    {
        try {
            $token = $this->oauth->checkAccessTokenRequest(
                $this->urlGen->getAbsoluteURL(
                    $this->urlGen->linkToRoute('grauphel.oauth.accessToken')
                )
            );
            $this->tokens->delete('verify', $token->tokenKey);

            $newToken = new Token('access');
            $newToken->tokenKey = $this->oauth->generateToken('a');
            $newToken->secret   = $this->oauth->generateToken('s');
            $newToken->user     = $token->user;
            $newToken->client   = $token->client;
            $this->tokens->store($newToken);

            return new FormResponse(
                array(
                    'oauth_token'        => $newToken->tokenKey,
                    'oauth_token_secret' => $newToken->secret,
                )
            );
        } catch (OAuthException $e) {
            return $this->oauth->errorResponse($e);
        }
    }

    /**
     * Log the user in and let him authorize that the app may access notes
     * OAuth step 2 of 3
     *
     * Page is not public and thus requires owncloud login
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function authorize()
    {
        $token = $this->verifyRequestToken();
        if (!$token instanceof Token) {
            return $token;
        }

        $clientTitle = 'unknown';
        $clientAgent = (string) $this->request->getParam('client', '');
        if ($clientAgent != '') {
            $cl = new Client();
            $clientTitle = $cl->getNiceName($clientAgent);
        }

        $res = new TemplateResponse('grauphel', 'oauthAuthorize');
        $res->setParams(
            array(
                'oauth_token' => $token->tokenKey,
                'clientTitle' => $clientTitle,
                'clientAgent' => $clientAgent,
                'formaction'  => $this->urlGen->linkToRoute(
                    'grauphel.oauth.confirm'
                ),
            )
        );

        //browsers apply form-action to the redirect after confirm, too
        $csp = new ContentSecurityPolicy();
        $csp->addAllowedFormActionDomain('http://localhost:*');
        $callbackSource = UrlHelper::getCspSource($token->callback);
        if ($callbackSource !== null) {
            $csp->addAllowedFormActionDomain($callbackSource);
        }
        $res->setContentSecurityPolicy($csp);

        return $res;
    }

    /**
     * User confirms or declines the authorization request
     * OAuth step 2.5 of 3
     */
    #[NoAdminRequired]
    public function confirm()
    {
        $token = $this->verifyRequestToken();
        if (!$token instanceof Token) {
            return $token;
        }

        try {
            $token = $this->tokens->loadAndDelete('temp', $token->tokenKey);
        } catch (OAuthException $e) {
            return new ErrorResponse($e->getMessage());
        }

        $authState = $this->request->getParam('auth') == 'ok';
        if ($authState === false) {
            //user declined

            //http://wiki.oauth.net/w/page/12238543/ProblemReporting
            $res = new RedirectResponse(
                UrlHelper::addParams(
                    $token->callback,
                    array(
                        'oauth_token'   => $token->tokenKey,
                        'oauth_problem' => 'permission_denied',
                    )
                )
            );
            $res->setStatus(Http::STATUS_SEE_OTHER);
            return $res;
        }

        //the user is logged in and authorized
        $newToken = new Token('verify');
        $newToken->tokenKey = $token->tokenKey;
        $newToken->secret   = $token->secret;
        $newToken->verifier = $this->oauth->generateToken('v');
        $newToken->user     = $this->user->getUID();
        $newToken->client   = (string) $this->request->getParam('client', '');

        $this->tokens->store($newToken);

        //redirect
        //FIXME: if no callback is given, show the token to the user
        $res = new RedirectResponse(
            UrlHelper::addParams(
                $token->callback,
                array(
                    'oauth_token'    => $newToken->tokenKey,
                    'oauth_verifier' => $newToken->verifier
                )
            )
        );
        $res->setStatus(Http::STATUS_SEE_OTHER);
        return $res;
    }

    protected function verifyRequestToken()
    {
        $reqToken = $this->request->getParam('oauth_token');
        if ($reqToken === null) {
            return new ErrorResponse('oauth_token missing');
        }

        if (!$this->oauth->validateToken($reqToken)) {
            return new ErrorResponse('Invalid token string');
        }

        try {
            $token = $this->tokens->load('temp', $reqToken);
        } catch (OAuthException $e) {
            return new ErrorResponse($e->getMessage());
        }

        return $token;
    }

    /**
     * Create and return a request token.
     * OAuth step 1 of 3
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function requestToken()
    {
        try {
            $callback = $this->oauth->checkRequestTokenRequest(
                $this->urlGen->getAbsoluteURL(
                    $this->urlGen->linkToRoute('grauphel.oauth.requestToken')
                )
            );

            //store token + callback URI for later
            $token = new Token('temp');
            $token->tokenKey = $this->oauth->generateToken('r');
            $token->secret   = $this->oauth->generateToken('s');
            $token->callback = $callback;

            $this->tokens->store($token);

            return new FormResponse(
                array(
                    'oauth_token'              => $token->tokenKey,
                    'oauth_token_secret'       => $token->secret,
                    'oauth_callback_confirmed' => 'true'
                )
            );
        } catch (OAuthException $e) {
            return $this->oauth->errorResponse($e);
        }
    }
}
