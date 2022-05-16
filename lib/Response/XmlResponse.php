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
namespace OCA\Grauphel\Response;

/**
 * Returns XML data
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class XmlResponse extends \OCP\AppFramework\Http\Response
{
    protected $xml;

    public function __construct($xml)
    {
        $this->setStatus(\OCP\AppFramework\Http::STATUS_OK);
        $this->addHeader('Content-Type', 'text/xml; charset=utf-8');
        $this->xml = $xml;
    }

    public function render()
    {
        return $this->xml;
    }
}
?>
