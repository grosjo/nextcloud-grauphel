<?php
/**
 * Part of grauphel
 *
 * PHP version 8
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link      http://cweiske.de/grauphel.htm
 */
namespace OCA\Grauphel\Auth;

use \OCA\Grauphel\Response\FormResponse;
use \OCA\Grauphel\Storage\TokenStorage;
use \OCP\AppFramework\Http;
use \OCP\IRequest;

/**
 * OAuth 1.0a service provider (RFC 5849), implemented in plain PHP
 * so that the PECL oauth extension is not needed.
 *
 * Tomboy clients use the fixed consumer key "anyone" and the consumer
 * secret is the same as the key.
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
    protected TokenStorage $tokens;
    protected IRequest $request;

    public function __construct(TokenStorage $tokens, IRequest $request)
    {
        $this->tokens  = $tokens;
        $this->request = $request;
    }

    public function validateToken($tokenKey)
    {
        return (bool) preg_match('#^[a-z0-9]+$#', $tokenKey);
    }

    /**
     * Create a new random token string
     *
     * @param string $prefix Single character to tell token types apart
     *
     * @return string
     */
    public function generateToken($prefix)
    {
        return $prefix . bin2hex(random_bytes(8));
    }

    /**
     * Verify a request to the request token endpoint (OAuth step 1).
     *
     * @param string $url Absolute URL of the endpoint
     *
     * @return string Callback URL sent by the client
     *
     * @throws OAuthException When the request is not valid
     */
    public function checkRequestTokenRequest($url)
    {
        $params = $this->getRequestParams();
        $callback = $this->requireParam($params, 'oauth_callback');
        $this->checkSignature($url, $params, '');
        return $callback;
    }

    /**
     * Verify a request to the access token endpoint (OAuth step 3).
     *
     * @param string $url Absolute URL of the endpoint
     *
     * @return Token The authorized verification token
     *
     * @throws OAuthException When the request is not valid
     */
    public function checkAccessTokenRequest($url)
    {
        $params = $this->getRequestParams();
        $tokenKey = $this->requireParam($params, 'oauth_token');
        $verifier = $this->requireParam($params, 'oauth_verifier');

        $token = $this->tokens->load('verify', $tokenKey);
        if (!hash_equals((string) $token->verifier, $verifier)) {
            throw new OAuthException(
                'Invalid verifier', OAuthException::VERIFIER_INVALID
            );
        }

        $this->checkSignature($url, $params, $token->secret);
        return $token;
    }

    /**
     * Verify an API request that is signed with an access token.
     *
     * @param string $url Absolute URL of the API endpoint
     *
     * @return Token The access token
     *
     * @throws OAuthException When the request is not valid
     */
    public function checkApiRequest($url)
    {
        $params = $this->getRequestParams();
        //conboy sends an empty token when it is not authorized yet
        $tokenKey = $this->requireParam($params, 'oauth_token');

        $token = $this->tokens->load('access', $tokenKey);
        $this->checkSignature($url, $params, $token->secret);

        if (time() - $token->lastuse > 60) {
            //time to update lastuse after at least a minute
            $this->tokens->updateLastUse($token->tokenKey);
        }
        return $token;
    }

    /**
     * Create the response for a failed OAuth request
     *
     * @param OAuthException $e Exception describing the problem
     *
     * @return FormResponse
     */
    public function errorResponse(OAuthException $e)
    {
        $res = new FormResponse(array('oauth_problem' => $e->problem));
        if ($e->problem == OAuthException::PARAMETER_ABSENT
            || $e->problem == OAuthException::SIGNATURE_METHOD_REJECTED
            || $e->problem == OAuthException::VERSION_REJECTED
        ) {
            $res->setStatus(Http::STATUS_BAD_REQUEST);
        } else {
            $res->setStatus(Http::STATUS_UNAUTHORIZED);
        }
        return $res;
    }

    /**
     * Check the protocol parameters and the signature of the current request
     *
     * @param string $url         Absolute URL of the endpoint
     * @param array  $params      Request parameters as list of name/value pairs
     * @param string $tokenSecret Secret of the token used in the request
     *
     * @return void
     *
     * @throws OAuthException When the request is not valid
     */
    protected function checkSignature($url, array $params, $tokenSecret)
    {
        $consumerKey = $this->requireParam($params, 'oauth_consumer_key');
        $method      = $this->requireParam($params, 'oauth_signature_method');
        $signature   = $this->requireParam($params, 'oauth_signature');

        $version = $this->getParam($params, 'oauth_version');
        if ($version !== null && $version !== '1.0') {
            throw new OAuthException(
                'Unsupported OAuth version', OAuthException::VERSION_REJECTED
            );
        }

        //tomboy assumes secret == key == "anyone"
        $key = rawurlencode($consumerKey) . '&' . rawurlencode($tokenSecret);

        if ($method == 'PLAINTEXT') {
            if (hash_equals($key, $signature)) {
                return;
            }
        } else if ($method == 'HMAC-SHA1' || $method == 'HMAC-SHA256') {
            $this->requireParam($params, 'oauth_timestamp');
            $this->requireParam($params, 'oauth_nonce');
            //the client may use the URL with or without "index.php/"
            $urls = array_unique(array($this->getCurrentUrl(), $url));
            foreach ($urls as $candidateUrl) {
                $expected = base64_encode(
                    hash_hmac(
                        $method == 'HMAC-SHA1' ? 'sha1' : 'sha256',
                        $this->getSignatureBaseString($candidateUrl, $params),
                        $key,
                        true
                    )
                );
                if (hash_equals($expected, $signature)) {
                    return;
                }
            }
        } else {
            throw new OAuthException(
                'Unsupported signature method: ' . $method,
                OAuthException::SIGNATURE_METHOD_REJECTED
            );
        }

        throw new OAuthException(
            'Invalid signature', OAuthException::SIGNATURE_INVALID
        );
    }

    /**
     * URL of the current request as seen by the client,
     * taking reverse proxy and overwrite* settings into account
     *
     * @return string Absolute URL without query string
     */
    protected function getCurrentUrl()
    {
        $path = strtok($this->request->getRequestUri(), '?');
        return $this->request->getServerProtocol() . '://'
            . $this->request->getServerHost() . $path;
    }

    /**
     * Build the signature base string (RFC 5849 section 3.4.1)
     *
     * @param string $url    Absolute URL of the endpoint
     * @param array  $params Request parameters as list of name/value pairs
     *
     * @return string
     */
    public function getSignatureBaseString($url, array $params)
    {
        $encoded = array();
        foreach ($params as list($name, $value)) {
            if ($name == 'oauth_signature' || $name == 'realm') {
                continue;
            }
            $encoded[] = array(rawurlencode($name), rawurlencode($value));
        }
        usort(
            $encoded,
            function ($a, $b) {
                return strcmp($a[0], $b[0]) ?: strcmp($a[1], $b[1]);
            }
        );
        $pairs = array();
        foreach ($encoded as list($name, $value)) {
            $pairs[] = $name . '=' . $value;
        }

        return strtoupper($this->request->getMethod())
            . '&' . rawurlencode($this->getBaseStringUri($url))
            . '&' . rawurlencode(implode('&', $pairs));
    }

    /**
     * Normalize the endpoint URL (RFC 5849 section 3.4.1.2)
     *
     * @param string $url Absolute URL
     *
     * @return string
     */
    protected function getBaseStringUri($url)
    {
        $parts  = parse_url($url);
        $scheme = strtolower($parts['scheme']);
        $uri    = $scheme . '://' . strtolower($parts['host']);
        if (isset($parts['port'])
            && !($scheme == 'http' && $parts['port'] == 80)
            && !($scheme == 'https' && $parts['port'] == 443)
        ) {
            $uri .= ':' . $parts['port'];
        }
        return $uri . (isset($parts['path']) ? $parts['path'] : '/');
    }

    /**
     * Collect the parameters of the current request from the
     * Authorization header, the query string and a form-encoded body
     * (RFC 5849 section 3.4.1.3.1).
     *
     * @return array List of array(name, value) pairs
     */
    protected function getRequestParams()
    {
        $params = array();

        $auth = $this->getAuthorizationHeader();
        if (strncasecmp($auth, 'OAuth ', 6) == 0) {
            preg_match_all(
                '/([a-zA-Z0-9_-]+)\s*=\s*(?:"([^"]*)"|([^,\s]*))/',
                substr($auth, 6), $matches, PREG_SET_ORDER
            );
            foreach ($matches as $match) {
                $name = rawurldecode($match[1]);
                //Tomboy repeats the query parameters in the header;
                // only protocol parameters belong there (RFC 5849 3.5.1)
                if (strncmp($name, 'oauth_', 6) != 0) {
                    continue;
                }
                $value = isset($match[3]) ? $match[3] : $match[2];
                $params[] = array($name, rawurldecode($value));
            }
        }

        $server = $this->request->server;
        if (isset($server['QUERY_STRING'])) {
            $params = array_merge(
                $params, $this->parseFormEncoded($server['QUERY_STRING'])
            );
        }

        if ($this->request->getMethod() == 'POST'
            && stripos(
                $this->request->getHeader('Content-Type'),
                'application/x-www-form-urlencoded'
            ) === 0
        ) {
            $body = file_get_contents('php://input');
            $params = array_merge($params, $this->parseFormEncoded($body));
        }

        return $params;
    }

    protected function getAuthorizationHeader()
    {
        $auth = $this->request->getHeader('Authorization');
        if ($auth != '') {
            return $auth;
        }
        //FastCGI and some Apache setups hide the original header
        $server = $this->request->server;
        foreach (array('REDIRECT_HTTP_AUTHORIZATION', 'HTTP_XAUTHORIZATION') as $key) {
            if (isset($server[$key]) && $server[$key] != '') {
                return $server[$key];
            }
        }
        return '';
    }

    /**
     * Split an application/x-www-form-urlencoded string into pairs,
     * keeping duplicate names.
     *
     * @param string $data Encoded data
     *
     * @return array List of array(name, value) pairs
     */
    protected function parseFormEncoded($data)
    {
        $params = array();
        foreach (explode('&', (string) $data) as $pair) {
            if ($pair === '') {
                continue;
            }
            $parts = explode('=', $pair, 2);
            $params[] = array(
                urldecode($parts[0]),
                isset($parts[1]) ? urldecode($parts[1]) : ''
            );
        }
        return $params;
    }

    protected function getParam(array $params, $name)
    {
        foreach ($params as list($paramName, $value)) {
            if ($paramName === $name) {
                return $value;
            }
        }
        return null;
    }

    protected function requireParam(array $params, $name)
    {
        $value = $this->getParam($params, $name);
        if ($value === null || $value === '') {
            throw new OAuthException(
                'Missing parameter: ' . $name,
                OAuthException::PARAMETER_ABSENT
            );
        }
        return $value;
    }
}
