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
 * Convert Tomboy note XML to reStructuredText.
 * Mainly used to paste the content of a note into an e-mail
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class ReStructuredText extends Base
{
    protected static $simpleMap = array(
        'bold'      => '**',
        'italic'    => '*',
        'monospace' => '``',
        'strikethrough' => '-',
        'highlight'  => '**',
    );

    public $internalLinkHandler;



    public function __construct()
    {
        $this->internalLinkHandler = array($this, 'internalLinkHandler');
    }

    /**
     * Converts the tomboy note XML into reStructuredText
     *
     * @param string $xmlContent Tomboy note content
     *
     * @return string Plain text
     */
    public function convert($xmlContent)
    {
        if (strpos($xmlContent, '</link:internal><link:internal>') !== false) {
            $xmlContent = $this->fixNastyLinks($xmlContent);
        }

        $rst = '';
        $reader = new XMLReader();
        $reader->xml(
            '<?xml version="1.0" encoding="utf-8"?>' . "\n"
            . '<content xmlns:size="size" xmlns:link="link">'
            . $xmlContent
            . '</content>'
        );

        $withinLink = false;
        $store = &$rst;
        $listLevel  = -1;
        $listPrefix = '';
        $listItemCount = 0;
        $heading = false;
        $headingLength = 0;
        while ($reader->read()) {
            switch ($reader->nodeType) {
            case XMLReader::ELEMENT:
                //echo $reader->name . "\n";
                if (isset(static::$simpleMap[$reader->name])) {
                    $store .= static::$simpleMap[$reader->name];
                } else if ($reader->name == 'list') {
                    ++$listLevel;
                    $listItemCount = 0;
                    $listPrefix = str_repeat('  ', $listLevel);
                } else if ($reader->name == 'list-item') {
                    ++$listItemCount;
                    if ($listItemCount == 1) {
                        $store .= "\n";
                    }
                    $store .= $listPrefix . '- ';
                } else if ($reader->name == 'size:large'
                    || $reader->name == 'size:huge'
                ) {
                    $store .= "\n";
                    $heading = true;
                } else if (substr($reader->name, 0, 5) == 'link:') {
                    $withinLink = true;
                    $linkText    = '';
                    $store       = &$linkText;
                }
                break;
            case XMLReader::END_ELEMENT:
                if (isset(static::$simpleMap[$reader->name])) {
                    $store .= static::$simpleMap[$reader->name];
                } else if ($reader->name == 'list') {
                    --$listLevel;
                    $listPrefix = str_repeat('  ', $listLevel);
                    if ($listLevel == -1) {
                        $store .= "\n";
                    }
                } else if ($reader->name == 'size:large') {
                    $store .= "\n" . str_repeat('-', $headingLength);
                    $heading = false;
                } else if ($reader->name == 'size:huge') {
                    $store .= "\n" . str_repeat('=', $headingLength);
                    $heading = false;
                } else if (substr($reader->name, 0, 5) == 'link:') {
                    $withinLink = false;
                    $store      = &$rst;
                    $linkUrl = htmlspecialchars_decode(strip_tags($linkText));
                    if ($reader->name == 'link:internal') {
                        $linkUrl = call_user_func($this->internalLinkHandler, $linkUrl);
                    } else {
                        $linkUrl = $this->fixLinkUrl($linkUrl);
                    }
                    $store .= $linkUrl;
                }
                break;
            case XMLReader::TEXT:
            case XMLReader::SIGNIFICANT_WHITESPACE:
                if ($heading) {
                    $headingLength = strlen(trim($reader->value));
                    $store .= trim($reader->value);
                } else {
                    $text = wordwrap($reader->value, 72 - 2 * $listLevel, "\n", true);
                    $parts = explode("\n", $text);
                    foreach ($parts as $k => $v) {
                        if ($k == 0) {
                            continue;
                        }
                        if ($v != '') {
                            $parts[$k] = str_repeat(' ', $listLevel * 2 + 2) . $v;
                        }
                    }
                    $store .= implode("\n", $parts);
                }
                break;
            default:
                throw new Exception(
                    'Unsupported XML node type: ' . $reader->nodeType
                );
            }
        }

        return $rst;
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
}
?>
