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
 * Token store
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class TokenStorage
{
    /**
     * Store the given token
     *
     * @param OAuth_Token $token Token object to store
     *
     * @return void
     */
    public function store(OAuth_Token $token)
    {
        \OC_DB::executeAudited(
            'INSERT INTO `*PREFIX*grauphel_oauth_tokens`'
            . '(`token_user`, `token_type`, `token_key`, `token_secret`, `token_verifier`, `token_callback`)'
            . ' VALUES(?, ?, ?, ?, ?, ?)',
            array(
                $token->user,
                $token->type,
                $token->tokenKey,
                (string) $token->secret,
                (string) $token->verifier,
                (string) $token->callback
            )
        );
    }

    /**
     * Load the token and destroy it.
     *
     * @param string $type     Token type: temp, access, verify
     * @param string $tokenKey Random token string to load
     *
     * @return OAuth_Token Stored token
     *
     * @throws OAuthException When token does not exist
     */
    public function loadAndDelete($type, $tokenKey)
    {
        try {
            $token = $this->load($type, $tokenKey);
            \OC_DB::executeAudited(
                'DELETE FROM `*PREFIX*grauphel_oauth_tokens`'
                . ' WHERE `token_key` = ? AND `token_type` = ?',
                array($tokenKey, $type)
            );
            return $token;
        } catch (OAuthException $e) {
            throw $e;
        }
    }


    /**
     * Load the token.
     *
     * @param string $type     Token type: temp, access, verify
     * @param string $tokenKey Random token string to load
     *
     * @return OAuth_Token Stored token
     *
     * @throws OAuthException When token does not exist or it is invalid
     */
    public function load($type, $tokenKey)
    {
        $tokenRow = \OC_DB::executeAudited(
            'SELECT * FROM `*PREFIX*grauphel_oauth_tokens`'
            . ' WHERE `token_key` = ? AND `token_type` = ?',
            array($tokenKey, $type)
        )->fetchRow();

        if ($tokenRow === false) {
            throw new OAuthException(
                'Unknown token: ' . $type . ' / ' . $tokenKey,
                OAUTH_TOKEN_REJECTED
            );
        }

        $token = $this->fromDb($tokenRow);
        if ($token->tokenKey != $tokenKey) {
            throw new OAuthException('Invalid token', OAUTH_TOKEN_REJECTED);
        }

        return $token;
    }

    /**
     * Load multiple tokens
     *
     * @param string $username User name
     * @param string $type     Token type: temp, access, verify
     *
     * @return array Array of Token objects
     */
    public function loadForUser($username, $type)
    {
        $result = \OC_DB::executeAudited(
            'SELECT * FROM `*PREFIX*grauphel_oauth_tokens`'
            . ' WHERE `token_user` = ? AND `token_type` = ?',
            array($username, $type)
        );

        $tokens = array();
        while ($tokenRow = $result->fetchRow()) {
            $tokens[] = $this->fromDb($tokenRow);
        }

        return $tokens;
    }

    protected function fromDb($tokenRow)
    {
        $token = new Token();
        $token->type     = $tokenRow['token_user'];
        $token->tokenKey = $tokenRow['token_key'];
        $token->secret   = $tokenRow['token_secret'];
        $token->user     = $tokenRow['token_user'];
        $token->verifier = $tokenRow['token_verifier'];
        $token->callback = $tokenRow['token_callback'];
        return $token;
    }
}
?>
