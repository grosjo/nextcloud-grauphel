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
namespace OCA\Grauphel\Converter;

/**
 * Base class to convert tomboy XML to some other format.
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class Base
{
    /**
     * Re-arranges the XML of formatted links to that clean link tags can
     * be generated.
     *
     * Tomboy 1.15.2 allows link formatting, and the resulting XML is a
     * mess of multiple(!) link tags that are within or around other formatting
     * tags.
     *
     * This method tries to re-arrange the links so that only a single link tag
     * appears with all the formatting inside.
     * 
     * @param string $xmlContent Tomboy note content
     *
     * @return string XML content, with re-arranged link tags.
     */
    protected function fixNastyLinks($xmlContent)
    {
        preg_match_all(
            '#(?:<.*>)?<link:internal>.+</link:internal><link:internal>.+</link:internal>#U',
            $xmlContent,
            $matches
        );

        foreach ($matches[0] as $nastyLink) {
            $cleaner = str_replace('</link:internal><link:internal>', '', $nastyLink);
            $cleaner = preg_replace('#<([a-z]+)><(link:internal)>#U', '<\2><\1>', $cleaner);
            $cleaner = preg_replace('#</(link:internal)></([a-z]+)>#U', '</\2></\1>', $cleaner);
            $cleaner = str_replace('</link:internal><link:internal>', '', $cleaner);
            $xmlContent = str_replace($nastyLink, $cleaner, $xmlContent);
        }

        return $xmlContent;
    }
}
?>
