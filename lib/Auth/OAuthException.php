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
namespace OCA\Grauphel\Auth;

/**
 * OAuth error
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class OAuthException extends \Exception
{
    /**
     * Problem names from the OAuth problem reporting extension
     *
     * @link https://wiki.oauth.net/w/page/12238543/ProblemReporting
     */
    public const PARAMETER_ABSENT          = 'parameter_absent';
    public const SIGNATURE_INVALID         = 'signature_invalid';
    public const SIGNATURE_METHOD_REJECTED = 'signature_method_rejected';
    public const TOKEN_REJECTED            = 'token_rejected';
    public const VERIFIER_INVALID          = 'verifier_invalid';
    public const VERSION_REJECTED          = 'version_rejected';

    /**
     * @var string One of the problem constants
     */
    public $problem;

    public function __construct($message, $problem = self::TOKEN_REJECTED)
    {
        parent::__construct($message);
        $this->problem = $problem;
    }
}
