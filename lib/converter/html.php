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
 * Convert Tomboy note XML to HTML
 *
 * Tomboy already ships with a converter:
 * https://git.gnome.org/browse/tomboy/tree/Tomboy/Addins/ExportToHtml/ExportToHtml.xsl
 * We cannot use it since we want nice callbacks, and we do not want to rely
 * on the PHP XSL extension, and we have to fix the links.
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class Html
{
    protected static $tagMap = array(
        'list'      => 'ul',
        'list-item' => 'li',
        'bold'      => 'b',
        'italic'    => 'i',
        'monospace' => 'tt',
    );

    protected static $styleClassMap = array(
        'strikethrough' => 'strikethrough',
        'highlight'  => 'highlight',
        'size:small' => 'small',
        'size:large' => 'large',
        'size:huge'  => 'huge',
    );

    public $internalLinkHandler;



    public function __construct()
    {
        $this->internalLinkHandler = array($this, 'internalLinkHandler');
    }

    /**
     * Converts the tomboy note XML into HTML
     *
     * @param string $xmlContent Tomboy note content
     *
     * @return string HTML
     */
    public function convert($xmlContent)
    {
        if (strpos($xmlContent, '</link:internal><link:internal>') !== false) {
            $xmlContent = $this->fixNastyLinks($xmlContent);
        }

        $html = '';
        $reader = new XMLReader();
        $reader->xml(
            '<?xml version="1.0" encoding="utf-8"?>' . "\n"
            . '<content xmlns:size="size" xmlns:link="link">'
            . $xmlContent
            . '</content>'
        );

        $withinLink = false;
        $store = &$html;
        while ($reader->read()) {
            switch ($reader->nodeType) {
            case XMLReader::ELEMENT:
                //echo $reader->name . "\n";
                if (isset(static::$tagMap[$reader->name])) {
                    $store .= '<' . static::$tagMap[$reader->name] . '>';
                } else if (isset(static::$styleClassMap[$reader->name])) {
                    $store .= '<span class="'
                        . static::$styleClassMap[$reader->name]
                        . '">';
                } else if (substr($reader->name, 0, 5) == 'link:') {
                    $withinLink = true;
                    $linkText    = '';
                    $store       = &$linkText;
                }
                break;
            case XMLReader::END_ELEMENT:
                if (isset(static::$tagMap[$reader->name])) {
                    $store .= '</' . static::$tagMap[$reader->name] . '>';
                } else if (isset(static::$styleClassMap[$reader->name])) {
                    $store .= '</span>';
                } else if (substr($reader->name, 0, 5) == 'link:') {
                    $withinLink = false;
                    $store      = &$html;
                    $linkUrl = htmlspecialchars_decode(strip_tags($linkText));
                    if ($reader->name == 'link:internal') {
                        $linkUrl = call_user_func($this->internalLinkHandler, $linkUrl);
                    } else {
                        $linkUrl = $this->fixLinkUrl($linkUrl);
                    }
                    $store .= '<a href="' . htmlspecialchars($linkUrl) . '">'
                        . $linkText
                        . '</a>';
                }
                break;
            case XMLReader::TEXT:
            case XMLReader::SIGNIFICANT_WHITESPACE:
                $store .= nl2br(htmlspecialchars($reader->value));
                break;
            default:
                throw new Exception(
                    'Unsupported XML node type: ' . $reader->nodeType
                );
            }
        }

        $html = str_replace("</ul><br />\n", "</ul>\n", $html);

        return $html;
    }

    /**
     * Fixes external URLs without a protocol
     *
     * @param string $linkUrl URL to fix
     *
     * @return string Fixed URL
     */
    protected function fixLinkUrl($linkUrl)
    {
        if ($linkUrl{0} == '/') {
            //Unix file path
            $linkUrl = 'file://' . $linkUrl;
        }
        return $linkUrl;
    }

    /**
     * Dummy internal link handler that simply adds ".htm" to the note title
     *
     * @param string $linkUrl Title of page that is linked
     *
     * @return string URL to link to
     */
    public function internalLinkHandler($linkUrl)
    {
        return $linkUrl . '.htm';
    }

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
