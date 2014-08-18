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

    protected static $instance;

    public static function get()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $deps = new self();
        /*
        $deps->notes = new Note_Storage_Flatfile();
        $deps->notes->setDataDir($dataDir);
        $deps->notes->setDeps($deps);

        $deps->urlGen = new UrlGen_Pretty();
        $deps->urlGen->setDeps($deps);
        /*
        $deps->frontend = new Frontend_Default();
        $deps->frontend->setDeps($deps);
        */

        $deps->tokens = new TokenStorage();

        self::$instance = $deps;
        return self::$instance;
    }
}
?>
