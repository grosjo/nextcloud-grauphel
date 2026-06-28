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
namespace OCA\Grauphel\Tools;

use \OCA\Grauphel\Storage\TokenStorage;

/**
 * Object container
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class Dependencies
{
    /**
     * @var Frontend\Default
     */
    public $frontend;

    /**
     * @var Note\Storage
     */
    public $noteStorage;

    /**
     * @var OAuth\Storage
     */
    public $oauthStorage;

    /**
     * @var IURLGenerator
     */
    public $urlGen;

    /**
     * @var \OCP\IDBConnection
     */
    public $db;

    /**
     * @var \OCA\Grauphel\Storage\TokenStorage
     */
    public $tokens;

    protected static $instance;

    public static function get()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $deps = new self();

        self::$instance = $deps;
        return self::$instance;
    }

    public static function init(\OCP\IDBConnection $db, \OCP\IURLGenerator $urlGen)
    {
        $deps = self::get();
        $deps->db = $db;
        $deps->urlGen = $urlGen;
        if ($deps->tokens === null) {
            $deps->tokens = new TokenStorage($db);
        }
        return $deps;
    }
}
?>
