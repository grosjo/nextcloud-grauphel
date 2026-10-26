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

    /**
     * Get the CSP source expression that matches the given URL:
     * "scheme://host:port" for http(s), "scheme:" for app schemes
     * like tomdroid://sync
     *
     * @param string $url Full URL
     *
     * @return string|null NULL when the URL has no usable scheme
     */
    public static function getCspSource($url)
    {
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'])
            || !preg_match('#^[a-z][a-z0-9+.-]*$#i', $parts['scheme'])
        ) {
            return null;
        }
        $scheme = strtolower($parts['scheme']);
        if (in_array($scheme, array('javascript', 'data', 'vbscript', 'blob', 'filesystem'))) {
            return null;
        }
        if ($scheme != 'http' && $scheme != 'https') {
            return $scheme . ':';
        }
        if (!isset($parts['host'])
            || !preg_match('#^[a-z0-9.-]+$#i', $parts['host'])
        ) {
            return null;
        }
        return $scheme . '://' . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
