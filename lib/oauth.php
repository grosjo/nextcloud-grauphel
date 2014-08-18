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
namespace OCA\Grauphel\Lib;

/**
 * Storage base class that implements note updating
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class OAuth
{
    /**
     * Token data store
     *
     * @var Token_Storage
     */
    protected $tokens;

    public function setDeps(Dependencies $deps)
    {
        $this->tokens = $deps->tokens;
    }

    /**
     * Register callbacks for the oauth dance.
     */
    public function registerHandler(\OAuthProvider $provider)
    {
        $provider->consumerHandler(array($this, 'lookupConsumer'));
        $provider->timestampNonceHandler(array($this, 'timestampNonceChecker'));
        return $this;
    }

    public function registerVerificationTokenHandler(\OAuthProvider $provider)
    {
        $provider->tokenHandler(array($this, 'verifyTokenHandler'));
        return $this;
    }

    public function registerAccessTokenHandler(\OAuthProvider $provider)
    {
        $provider->tokenHandler(array($this, 'accessTokenHandler'));
        return $this;
    }

    public function validateToken($tokenKey)
    {
        return (bool) preg_match('#^[a-z0-9]+$#', $tokenKey);
    }

    public function lookupConsumer(\OAuthProvider $provider)
    {
        //tomboy assumes secret==key=="anyone"
        $provider->consumer_secret = $provider->consumer_key;//'anyone';
        $provider->addRequiredParameter('oauth_callback');

        return OAUTH_OK;
    }

    public function timestampNonceChecker(\OAuthProvider $provider)
    {
        //var_dump($provider->nonce, $provider->timestamp);
        //OAUTH_BAD_NONCE
        //OAUTH_BAD_TIMESTAMP
        return OAUTH_OK;
    }

    public function verifyTokenHandler(\OAuthProvider $provider)
    {
        $token = $this->tokens->load('verify', $provider->token);
        if ($provider->verifier == '') {
            return OAUTH_VERIFIER_INVALID;
        }
        if ($provider->verifier != $token->verifier) {
            return OAUTH_VERIFIER_INVALID;
        }

        $provider->token_secret = $token->secret;
        return OAUTH_OK;
    }

    public function accessTokenHandler(\OAuthProvider $provider)
    {
        $token = $this->tokens->load('access', $provider->token);
        $provider->token_secret = $token->secret;
        return OAUTH_OK;
    }

    public function verifyOAuthUser($username, $url)
    {
        try {
            $provider = new \OAuthProvider();
            $this->registerHandler($provider);
            $this->registerAccessTokenHandler($provider);
            //do not use "user" in signature
            $provider->setParam('user', null);

            $provider->checkOAuthRequest($url);

            $token = $this->tokens->load('access', $provider->token);
            if ($token->user != $username) {
                errorOut('Invalid user');
            }
        } catch (\OAuthException $e) {
            $this->error($e);
        }
    }

    public function error(\OAuthException $e)
    {
        header('HTTP/1.0 400 Bad Request');
        //header('Content-type: application/x-www-form-urlencoded');
        echo \OAuthProvider::reportProblem($e);
        //var_dump($e);
        exit(1);
    }
}
?>
