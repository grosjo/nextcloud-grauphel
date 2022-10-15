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

/**
 * URL helper methods
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class UrlHelper
{
    public static function addParams($url, $arParams)
    {
        $parts = array();
        foreach($arParams as $key => $val) {
            if ($val != '') {
                $parts[] = urlencode($key) . '=' . urlencode($val);
            }
        }
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $sep . implode('&', $parts);
    }
}
?>
