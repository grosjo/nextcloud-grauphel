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
 * Owncloud frontend
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class GuiController extends Controller
{
    /**
     * constructor of the controller
     *
     * @param string   $appName Name of the app
     * @param IRequest $request Instance of the request
     */
    public function __construct($appName, \OCP\IRequest $request, $user, $urlGen)
    {
        parent::__construct($appName, $request);
        $this->user   = $user;
        $this->urlGen = $urlGen;

        //default http header: we assume something is broken
        header('HTTP/1.0 500 Internal Server Error');
    }

    /**
     * Main page /
     *
     * Tomdroid wants this to be a public page. Sync fails otherwise.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function index()
    {
        try {
            $this->checkDeps();
        } catch (\Exception $e) {
            $res = new TemplateResponse('grauphel', 'error');
            $res->setParams(
                array(
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                )
            );
            return $res;
        }

        $res = new TemplateResponse('grauphel', 'index');
        $res->setParams(
            array(
                'apiroot' => $this->getApiRootUrl(),
                'apiurl'  => $this->urlGen->linkToRoute('grauphel.api.index')
            )
        );
        $this->addNavigation($res);
        $this->addStats($res);
        return $res;
    }

    /**
     * Show contents of a note
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function note($guid)
    {
        $res = new TemplateResponse('grauphel', 'gui-note');

        $note = $this->getNotes()->load($guid, false);
        if ($note === null) {
            return new ErrorResponse('Note does not exist');
        }

        $converter = new \OCA\Grauphel\Converter\Html();
        $converter->internalLinkHandler = array($this, 'noteLinkHandler');

        try {
            $contentHtml = $converter->convert($note->{'note-content'});
        } catch (\OCA\Grauphel\Converter\Exception $e) {
            $contentHtml = '<div class="error">'
                . '<p>There was an error converting the note to HTML:</p>'
                . '<blockquote><tt>' . htmlspecialchars($e->getMessage()) . '</tt></blockquote>'
                . '<p>Please open a bug report at'
                . ' <a class="lined" href="http://github.com/cweiske/grauphel/issues">'
                . 'github.com/cweiske/grauphel/issues</a>'
                . ' and attach the XML version of the note.'
                . '</div>';
        }

        $res->setParams(
            array(
                'note' => $note,
                'note-content' => $contentHtml,
                'links' => array(
                    'json' => $this->urlGen->linkToRoute(
                        'grauphel.api.note', array(
                            'guid' => $guid, 'username' => $this->user->getUid()
                        )
                    ),
                    'xml' => $this->urlGen->linkToRoute(
                        'grauphel.notes.xml', array('guid' => $guid)
                    ),
                )
            )
        );

        $selectedRawtag = 'grauphel:special:untagged';
        if (count($note->tags) > 0) {
            $selectedRawtag = $note->tags[0];
        }

        $this->addNavigation($res, $selectedRawtag);
        return $res;
    }

    public function noteLinkHandler($noteTitle)
    {
        $guid = $this->getNotes()->loadGuidByTitle($noteTitle);
        if ($guid === null) {
            return '#';
        }
        return $this->urlGen->linkToRoute(
            'grauphel.gui.note', array('guid' => $guid)
        );
    }

    /**
     * Show all notes of a tag
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function tag($rawtag)
    {
        $notes = $this->getNotes()->loadNotesOverview(null, $rawtag, true);
        usort(
            $notes,
            function($noteA, $noteB) {
                return strcmp($noteA['title'], $noteB['title']);
            }
        );

        foreach ($notes as &$note) {
            $diffInDays = intval(
                (time() - strtotime($note['last-change-date'])) / 86400
            );
            $value = 0 + $diffInDays;
            if ($value > 160) {
                $value = 160;
            }
            $note['dateColor'] = '#' . str_repeat(sprintf('%02X', $value), 3);
        }

        $res = new TemplateResponse('grauphel', 'tag');
        $res->setParams(
            array(
                'tag'    => $this->getPrettyTagName($rawtag),
                'rawtag' => $rawtag,
                'notes'  => $notes,
            )
        );
        $this->addNavigation($res, $rawtag);

        return $res;
    }

    /**
     * Show access tokens
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function tokens()
    {
        $tokens = new TokenStorage();
        $res = new TemplateResponse('grauphel', 'tokens');
        $res->setParams(
            array(
                'tokens' => $tokens->loadForUser(
                    $this->user->getUid(), 'access'
                ),
                'client' => new Client(),
                'username' => $this->user->getUid(),
            )
        );
        $this->addNavigation($res, null);

        return $res;
    }

    /**
     * Allow the user to clear his database
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function database($reset = null)
    {
        $res = new TemplateResponse('grauphel', 'gui-database');
        $res->setParams(array('reset' => $reset));
        $this->addNavigation($res, null);
        $this->addStats($res);

        return $res;
    }

    /**
     * Resets the database by deleting all notes and deleting the user's
     * sync data.
     *
     * @NoAdminRequired
     */
    public function databaseReset()
    {
        $reset = false;
        if ($_POST['username'] != '' && $_POST['username'] == $this->user->getUid()) {
            $notes = $this->getNotes();
            $notes->deleteAll();
            $notes->deleteSyncData();
            $reset = true;
        }

        return $this->database($reset);
    }

    protected function addNavigation(TemplateResponse $res, $selectedRawtag = null)
    {
        $nav = new \OCP\Template('grauphel', 'appnavigation', '');
        $nav->assign('apiroot', $this->getApiRootUrl());
        $nav->assign('tags', array());

        $params = $res->getParams();
        $params['appNavigation'] = $nav;
        $res->setParams($params);

        if ($this->user === null) {
            return;
        }

        $rawtags = $this->getNotes()->getTags();
        sort($rawtags);
        array_unshift(
            $rawtags,
            'grauphel:special:all', 'grauphel:special:untagged'
        );

        $tags = array();
        foreach ($rawtags as $rawtag) {
            $name = $this->getPrettyTagName($rawtag);
            if ($name !== false) {
                $tags[] = array(
                    'name' => $name,
                    'id'   => $rawtag,
                    'href' => $this->urlGen->linkToRoute(
                        'grauphel.gui.tag', array('rawtag' => $rawtag)
                    ),
                    'selected' => $rawtag == $selectedRawtag,
                );
            }
        }
        $nav->assign('tags', $tags);
    }

    protected function addStats(TemplateResponse $res)
    {
        if ($this->user === null) {
            return;
        }

        $username = $this->user->getUid();
        $notes  = $this->getNotes();
        $tokens = new \OCA\Grauphel\Lib\TokenStorage();

        $nav = new \OCP\Template('grauphel', 'indexStats', '');
        $nav->assign('notes', count($notes->loadNotesOverview()));
        $nav->assign('syncrev', $notes->loadSyncData()->latestSyncRevision);
        $nav->assign('tokens', count($tokens->loadForUser($username, 'access')));

        $params = $res->getParams();
        $params['stats'] = $nav;
        $res->setParams($params);
    }

    protected function checkDeps()
    {
        if (!class_exists('OAuthProvider')) {
            throw new \Exception('PHP extension "oauth" is required', 1001);
        }
    }

    protected function getApiRootUrl()
    {
        //we need to remove the trailing / for tomdroid and conboy
        return rtrim(
            $this->urlGen->getAbsoluteURL(
                $this->urlGen->linkToRoute('grauphel.gui.index')
            ),
            '/'
        );
    }

    protected function getNotes()
    {
        $username = $this->user->getUid();
        $notes  = new \OCA\Grauphel\Lib\NoteStorage($this->urlGen);
        $notes->setUsername($username);
        return $notes;
    }

    protected function getPrettyTagName($rawtag)
    {
        if (substr($rawtag, 0, 16) == 'system:notebook:') {
            return substr($rawtag, 16);
        } else if (substr($rawtag, 0, 17) == 'grauphel:special:') {
            return '*' . substr($rawtag, 17) . '*';
        }
        return false;
    }
}
?>
