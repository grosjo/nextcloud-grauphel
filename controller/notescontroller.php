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
namespace OCA\Grauphel\Controller;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCA\Grauphel\Lib\Client;
use \OCA\Grauphel\Lib\TokenStorage;
use \OCA\Grauphel\Lib\Response\ErrorResponse;

/**
 * Owncloud frontend: Notes
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class NotesController extends Controller
{
    /**
     * constructor of the controller
     *
     * @param string   $appName Name of the app
     * @param IRequest $request Instance of the request
     */
    public function __construct($appName, \OCP\IRequest $request, $user)
    {
        parent::__construct($appName, $request);
        $this->user   = $user;

        //default http header: we assume something is broken
        header('HTTP/1.0 500 Internal Server Error');
    }

    /**
     * Output a note in tomboy XML format
     *
     * @link https://wiki.gnome.org/Apps/Tomboy/NoteXmlFormat
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function xml($guid)
    {
        $note = $this->getNotes()->load($guid, false);

        $xw = new \XMLWriter();
        $xw->openMemory();
        $xw->startDocument('1.0', 'utf-8');

        $xw->startElementNS(null, 'note', 'http://beatniksoftware.com/tomboy');
        $xw->writeAttribute('version', '0.3');
        $xw->writeAttribute('xmlns:link', 'http://beatniksoftware.com/tomboy/link');
        $xw->writeAttribute('xmlns:size', 'http://beatniksoftware.com/tomboy/size');

        $xw->writeElement('title', $note->title);
        $xw->startElement('text');
        $xw->writeAttribute('xml:space', 'preserve');

        $xw->startElement('note-content');
        $xw->writeAttribute('version', $note->{'note-content-version'});
        $xw->writeRaw($note->{'note-content'});
        $xw->endElement();//note-content
        $xw->endElement();//text

        $xw->writeElement('last-change-date', $note->{'last-change-date'});
        $xw->writeElement('last-metadata-change-date', $note->{'last-metadata-change-date'});
        $xw->writeElement('create-date', $note->{'create-date'});
        $xw->writeElement('cursor-position', 0);
        $xw->writeElement('width', 450);
        $xw->writeElement('height', 360);
        $xw->writeElement('x', 0);
        $xw->writeElement('y', 0);
        $xw->writeElement('open-on-startup', $note->{'open-on-startup'});

        $xw->endElement();//note

        return new \OCA\Grauphel\Response\XmlResponse($xw->outputMemory());
    }

    protected function getNotes()
    {
        $username = $this->user->getUid();
        $notes  = new \OCA\Grauphel\Lib\NoteStorage($this->urlGen);
        $notes->setUsername($username);
        return $notes;
    }
}
?>
