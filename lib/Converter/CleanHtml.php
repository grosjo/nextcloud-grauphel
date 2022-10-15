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
use \XMLReader;

/**
 * Convert Tomboy note XML to HTML that can be used (nearly) standalone.
 * This means it tries to use as many native tags as possible and
 * does not rely on classes so much.
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class CleanHtml extends Html
{
    protected static $tagMap = array(
        'list'      => 'ul',
        'list-item' => 'li',
        'bold'      => 'b',
        'italic'    => 'i',

        'size:large' => 'h3',
        'size:huge'  => 'h2',

        'strikethrough' => 'del',
        'highlight'     => 'ins',
    );

    protected static $styleClassMap = array(
        'size:small' => 'small',
    );

    protected static $styleMap = array(
        'monospace' => 'font-family:monospace; white-space: pre-wrap'
    );

    /**
     * Converts the tomboy note XML into HTML.
     * Cleans HTML a bit up after it has been generated with the clean tags.
     *
     * @param string $xmlContent Tomboy note content
     *
     * @return string HTML
     */
    public function convert($xmlContent)
    {
        $html = parent::convert($xmlContent);
        $html = str_replace('</h2><br />', '</h2>', $html);
        $html = str_replace('</h3><br />', '</h3>', $html);
        $html = str_replace("<br />\n</h2>", "</h2>\n", $html);
        $html = str_replace("<br />\n</h3>", "</h3>\n", $html);
        $html = str_replace("<br />\n</li>", "</li>\n", $html);
        $html = str_replace("<br />\n<ul>", "<ul>\n", $html);
        return $html;
    }
}
?>
