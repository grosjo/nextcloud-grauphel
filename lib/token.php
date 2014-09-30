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
 * OAuth token with some additional data
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class Token
{
    /**
     * One of: temp, access, verify
     *
     * @var string
     */
    public $type;

    /**
     * Actual random token string
     *
     * @var string
     */
    public $tokenKey;

    /**
     * Matching secret for the token string
     *
     * @var string
     */
    public $secret;

    /**
     * User name for which the token is valid
     *
     * @var string
     */
    public $user;

    /**
     * Verification string.
     * Only used when $type == 'verify'
     *
     * @var string
     */
    public $verifier;

    /**
     * Callback URL for temp tokens
     *
     * @var string
     */
    public $callback;

    /**
     * Client name/identifier (user agent)
     *
     * @var string
     */
    public $client;

    /**
     * Unix timestamp when the token was used last
     *
     * @var integer
     */
    public $lastuse;

    public function __construct($type = null)
    {
        $this->type = $type;
    }
}
?>
