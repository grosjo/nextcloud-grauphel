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
 * Client identification helper
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class Client
{
    /**
     * @param string $header Value of the X-Tomboy-Client HTTP header
     */
    public function getClient($header)
    {
        if ($header != '') {
            $client = $header;
            $doublepos = strpos($client, ', org.tomdroid');
            if ($doublepos !== false) {
                //https://bugs.launchpad.net/tomdroid/+bug/1375436
                //X-Tomboy-Client header is sent twice
                $client = substr($client, 0, $doublepos);
            }
            return $client;
        }

        return false;
    }

    public function getNiceName($client)
    {
        if (substr($client, 0, 12) == 'org.tomdroid') {
            //org.tomdroid v0.7.5, build 14, Android v4.4.2, innotek GmbH/VirtualBox
            return 'Tomdroid';
        }
        return $client;
    }

}
