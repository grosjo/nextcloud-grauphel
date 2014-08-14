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
class OAuthController extends Controller
{
    /**
     * Handle out an access token after verifying the verification token
     * OAuth step 3 of 3
     */
    public function accessTokenAction()
    {
        $oauth = new OAuth();
        $oauth->setDeps($this->deps);

        try {
            $provider = new \OAuthProvider();
            $oauth->registerHandler($provider)
                ->registerVerificationTokenHandler($provider);
            $provider->checkOAuthRequest($this->deps->urlGen->oauthAccessToken());

            $token = $this->deps->tokens->loadAndDelete('verify', $provider->token);

            $newToken = new OAuth_Token('access');
            $newToken->tokenKey = 'a' . bin2hex($provider->generateToken(8));
            $newToken->secret   = 's' . bin2hex($provider->generateToken(8));
            $newToken->user     = $token->user;
            $this->deps->tokens->store($newToken);

            $this->deps->renderer->sendFormData(
                array(
                    'oauth_token'        => $newToken->tokenKey,
                    'oauth_token_secret' => $newToken->secret,
                )
            );
            exit(0);
        } catch (\OAuthException $e) {
            $oauth->error($e);
        }
    }

    /**
     * Log the user in and let him authorize that the app may access notes
     * OAuth step 2 of 3
     */
    public function authorizeAction()
    {
        $oauth = new OAuth();
        $oauth->setDeps($this->deps);

        if (!isset($_REQUEST['oauth_token'])) {
            $this->deps->renderer->errorOut('oauth_token missing');
        }
        if (!$oauth->validateToken($_REQUEST['oauth_token'])) {
            $this->deps->renderer->errorOut('Invalid token string');
        }

        $reqToken = $_REQUEST['oauth_token'];

        try {
            $token = $this->deps->tokens->load('temp', $reqToken);
        } catch (OAuth_Exception $e) {
            $this->deps->renderer->errorOut($e->getMessage());
        }

        $authState = $this->deps->frontend->doAuthorize(
            $this->deps->urlGen->current()
        );
        if ($authState === null) {
            //this should not happen; doAuthorize() must block
            exit(1);
        }

        try {
            $token = $this->deps->tokens->loadAndDelete('temp', $reqToken);
        } catch (OAuth_Exception $e) {
            $this->deps->renderer->errorOut($e->getMessage());
        }

        if ($authState === false) {
            //user declined

            //http://wiki.oauth.net/w/page/12238543/ProblemReporting
            $callback = $this->deps->urlGen->addParams(
                $token->callback,
                array(
                    'oauth_token'   => $token->tokenKey,
                    'oauth_problem' => 'permission_denied',
                )
            );
            header('Location: ' . $callback, true, 302);
            exit(0);
        }

        //the user is logged in and authorized
        $provider = new \OAuthProvider();

        $newToken = new OAuth_Token('verify');
        $newToken->tokenKey = $token->tokenKey;
        $newToken->secret   = $token->secret;
        $newToken->verifier = 'v' . bin2hex($provider->generateToken(8));
        $newToken->user     = $this->deps->frontend->getUser();

        $this->deps->tokens->store($newToken);

        //redirect
        //FIXME: if no callback is given, show the token to the user
        $callback = $this->deps->urlGen->addParams(
            $token->callback,
            array(
                'oauth_token'    => $newToken->tokenKey,
                'oauth_verifier' => $newToken->verifier
            )
        );

        header('Location: ' . $callback, true, 302);
        exit();
    }

    /**
     * Create and return a request token.
     * OAuth step 1 of 3
     */
    public function requestTokenAction()
    {
        $oauth = new OAuth();
        $oauth->setDeps($this->deps);

        try {
            $provider = new \OAuthProvider();
            $oauth->registerHandler($provider);
            $provider->isRequestTokenEndpoint(true);
            $provider->checkOAuthRequest($this->deps->urlGen->oauthRequestToken());

            //store token + callback URI for later
            $token = new OAuth_Token('temp');
            $token->tokenKey = 'r' . bin2hex($provider->generateToken(8));
            $token->secret   = 's' . bin2hex($provider->generateToken(8));
            $token->callback = $provider->callback;

            $this->deps->tokens->store($token);

            $this->deps->renderer->sendFormData(
                array(
                    'oauth_token'              => $token->tokenKey,
                    'oauth_token_secret'       => $token->secret,
                    'oauth_callback_confirmed' => 'TRUE'
                )
            );
        } catch (\OAuthException $e) {
            $oauth->error($e);
        }
    }
}
?>
