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
use \OCP\AppFramework\Http\ContentSecurityPolicy;
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\AppFramework\Http\TemplateResponse;

use \OCA\Grauphel\Auth\Client;
use \OCA\Grauphel\Auth\Token;
use \OCA\Grauphel\Auth\OAuth;
use \OCA\Grauphel\Tools\Dependencies;
use \OCA\Grauphel\Response\ErrorResponse;
use \OCA\Grauphel\Response\FormResponse;
use \OCA\Grauphel\Auth\OAuthException;
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

    /**
     * constructor of the controller
     *
     * @param string   $appName Name of the app
     * @param IRequest $request Instance of the request
     */
    public function __construct($appName, \OCP\IRequest $request, $user)
    {
        parent::__construct($appName, $request);
        $this->user = $user;
        $this->deps = Dependencies::get();

        //default http header: we assume something is broken
        header('HTTP/1.0 500 Internal Server Error');
    }

    /**
     * Handle out an access token after verifying the verification token
     * OAuth step 3 of 3
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function accessToken()
    {
        $oauth = new OAuth();
        $oauth->setDeps($this->deps);
        $urlGen = $this->deps->urlGen;

        try {
            $provider = OAuth::getProvider();
            $oauth->registerHandler($provider)
                ->registerVerificationTokenHandler($provider);
            $provider->checkOAuthRequest(
                $urlGen->getAbsoluteURL(
                    $urlGen->linkToRoute('grauphel.oauth.accessToken')
                )
            );

            $token = $this->deps->tokens->loadAndDelete('verify', $provider->token);

            $newToken = new Token('access');
            $newToken->tokenKey = 'a' . bin2hex($provider->generateToken(8));
            $newToken->secret   = 's' . bin2hex($provider->generateToken(8));
            $newToken->user     = $token->user;
            $newToken->client   = $token->client;
            $this->deps->tokens->store($newToken);

            return new FormResponse(
                array(
                    'oauth_token'        => $newToken->tokenKey,
                    'oauth_token_secret' => $newToken->secret,
                )
            );
        } catch (OAuthException $e) {
            return new ErrorResponse($e->getMessage());
        } catch (\OAuthException $e) {
            $oauth->error($e);
        }
    }

    /**
     * Log the user in and let him authorize that the app may access notes
     * OAuth step 2 of 3
     *
     * Page is not public and thus requires owncloud login
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function authorize()
    {
        $token = $this->verifyRequestToken();
        if (!$token instanceof Token) {
            return $token;
        }

        $clientTitle = 'unknown';
        $clientAgent = '';
        if (isset($_GET['client'])) {
            $clientAgent = $_GET['client'];
            $cl = new Client();
            $clientTitle = $cl->getNiceName($clientAgent);
        }

        $res = new TemplateResponse('grauphel', 'oauthAuthorize');
        $res->setParams(
            array(
                'oauth_token' => $token->tokenKey,
                'clientTitle' => $clientTitle,
                'clientAgent' => $clientAgent,
                'formaction'  => $this->deps->urlGen->linkToRoute(
                    'grauphel.oauth.confirm'
                ),
            )
        );

        $csp = new ContentSecurityPolicy();
        $csp->addAllowedFormActionDomain('http://localhost:*');
        $res->setContentSecurityPolicy($csp);

        return $res;
    }

    /**
     * User confirms or declines the authorization request
     * OAuth step 2.5 of 3
     *
     * @NoAdminRequired
     */
    public function confirm()
    {
        $token = $this->verifyRequestToken();
        $oauth = new OAuth();
        $oauth->setDeps($this->deps);

        try {
            $token = $this->deps->tokens->loadAndDelete('temp', $token->tokenKey);
        } catch (OAuthException $e) {
            return new ErrorResponse($e->getMessage());
        }

        $authState = isset($_POST['auth']) && $_POST['auth'] == 'ok';
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

        $clientAgent = '';
        if (isset($_POST['client'])) {
            $clientAgent = $_POST['client'];
        }

        //the user is logged in and authorized
        $provider = OAuth::getProvider();

        $newToken = new Token('verify');
        $newToken->tokenKey = $token->tokenKey;
        $newToken->secret   = $token->secret;
        $newToken->verifier = 'v' . bin2hex($provider->generateToken(8));
        $newToken->user     = $this->user->getUID();
        $newToken->client   = $clientAgent;

        $this->deps->tokens->store($newToken);

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
        if (!isset($_REQUEST['oauth_token'])) {
            return new ErrorResponse('oauth_token missing');
        }

        $oauth = new OAuth();
        $oauth->setDeps($this->deps);
        if (!$oauth->validateToken($_REQUEST['oauth_token'])) {
            return new ErrorResponse('Invalid token string');
        }

        $reqToken = $_REQUEST['oauth_token'];

        try {
            $token = $this->deps->tokens->load('temp', $reqToken);
        } catch (OAuthException $e) {
            return new ErrorResponse($e->getMessage());
        }

        return $token;
    }

    /**
     * Create and return a request token.
     * OAuth step 1 of 3
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function requestToken()
    {
        $oauth = new OAuth();
        $oauth->setDeps($this->deps);
        $urlGen = $this->deps->urlGen;

        try {
            $provider = OAuth::getProvider();
            $oauth->registerHandler($provider);
            $provider->isRequestTokenEndpoint(true);
            $provider->checkOAuthRequest(
                $urlGen->getAbsoluteURL(
                    $urlGen->linkToRoute('grauphel.oauth.requestToken')
                )
            );

            //store token + callback URI for later
            $token = new Token('temp');
            $token->tokenKey = 'r' . bin2hex($provider->generateToken(8));
            $token->secret   = 's' . bin2hex($provider->generateToken(8));
            $token->callback = $provider->callback;

            $this->deps->tokens->store($token);

            return new FormResponse(
                array(
                    'oauth_token'              => $token->tokenKey,
                    'oauth_token_secret'       => $token->secret,
                    'oauth_callback_confirmed' => 'true'
                )
            );
        } catch (OAuthException $e) {
            return new ErrorResponse($e->getMessage());
        } catch (\OAuthException $e) {
            $oauth->error($e);
        }
    }
}
?>
