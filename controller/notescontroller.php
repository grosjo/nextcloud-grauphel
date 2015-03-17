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
     * Output a note as a standalone HTML file
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function html($guid)
    {
        $note = $this->getNotes()->load($guid, false);
        if ($note === null) {
            $res = new ErrorResponse('Note does not exist');
            $res->setStatus(\OCP\AppFramework\Http::STATUS_NOT_FOUND);
            return $res;
        }

        $xw = new \XMLWriter();
        $xw->openMemory();
        $xw->setIndent(true);
        $xw->setIndentString(' ');
        $xw->startDocument('1.0', 'utf-8');
        $xw->writeRaw(
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"'
            . ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
            . "\n"
        );

        $xw->startElementNS(null, 'html', 'http://www.w3.org/1999/xhtml');

        //head
        $xw->startElement('head');
        $xw->writeElement('title', $note->title);

        $xw->startElement('meta');
        $xw->writeAttribute('name', 'author');
        $xw->writeAttribute('content', $this->user->getDisplayName());
        $xw->endElement();

        $xw->startElement('meta');
        $xw->writeAttribute('http-equiv', 'Content-Type');
        $xw->writeAttribute('content', 'text/html; charset=utf-8');
        $xw->endElement();

        $xw->startElement('link');
        $xw->writeAttribute('rel', 'schema.DC');
        $xw->writeAttribute('href', 'http://purl.org/dc/elements/1.1/');
        $xw->endElement();

        $xw->startElement('meta');
        $xw->writeAttribute('name', 'DC.date.created');
        $xw->writeAttribute(
            'content', date('c', strtotime($note->{'create-date'}))
        );
        $xw->endElement();

        $xw->startElement('meta');
        $xw->writeAttribute('name', 'DC.date.modified');
        $xw->writeAttribute(
            'content', date('c', strtotime($note->{'last-change-date'}))
        );
        $xw->endElement();

        $xw->endElement();//head

        //body
        $xw->startElement('body');

        $xw->writeElement('h1', $note->title);

        $converter = new \OCA\Grauphel\Converter\CleanHtml();
        $converter->internalLinkHandler = array($this, 'htmlNoteLinkHandler');
        try {
            $xw->writeRaw(
                $converter->convert($note->{'note-content'})
            );
        } catch (\OCA\Grauphel\Converter\Exception $e) {
            $res = new ErrorResponse(
                'Error converting note to HTML.'
                . ' Please repport a bug to the grauphel developers.'
            );
            $res->setStatus(\OCP\AppFramework\Http::STATUS_NOT_FOUND);
            return $res;
        }

        $xw->endElement();//body

        $xw->endElement();//html
        return new \OCA\Grauphel\Response\XmlResponse($xw->outputMemory());
    }

    public function htmlNoteLinkHandler($noteTitle)
    {
        return urlencode($noteTitle) . '.html';
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
        if ($note === null) {
            $res = new ErrorResponse('Note does not exist');
            $res->setStatus(\OCP\AppFramework\Http::STATUS_NOT_FOUND);
            return $res;
        }

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
