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
 * Returns plain text
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class TextResponse extends \OCP\AppFramework\Http\Response
{
    protected $text;

    public function __construct($text)
    {
        $this->setStatus(\OCP\AppFramework\Http::STATUS_OK);
        $this->addHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->text = $text;
    }

    public function render()
    {
        return $this->text;
    }
}
?>
