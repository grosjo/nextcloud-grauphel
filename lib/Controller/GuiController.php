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
use \OCP\AppFramework\Http\Attribute\NoAdminRequired;
use \OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use \OCP\AppFramework\Http\Attribute\PublicPage;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IDateTimeFormatter;
use \OCP\IURLGenerator;
use \OCA\Grauphel\Storage\NoteStorage;
use \OCA\Grauphel\Storage\TokenStorage;

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
    protected $user;
    protected IURLGenerator $urlGen;
    protected IDateTimeFormatter $dateFormatter;
    protected NoteStorage $notes;
    protected TokenStorage $tokens;

    public function __construct(
        $appName, \OCP\IRequest $request,
        \OCP\IUserSession $userSession, IURLGenerator $urlGen,
        IDateTimeFormatter $dateFormatter,
        NoteStorage $notes, TokenStorage $tokens
    ) {
        parent::__construct($appName, $request);
        $this->user            = $userSession->getUser();
        $this->urlGen          = $urlGen;
        $this->dateFormatter   = $dateFormatter;
        $this->notes           = $notes;
        $this->tokens          = $tokens;
    }

    /**
     * Main page /
     *
     * Tomdroid wants this to be a public page. Sync fails otherwise.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function index()
    {
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function note($guid)
    {
        $res = new TemplateResponse('grauphel', 'gui-note');

        $note = $this->getNotes()->load($guid, false);
        if ($note === null) {
            $res = new \OCA\Grauphel\Response\ErrorResponse('Note does not exist');
            $res->setStatus(\OCP\AppFramework\Http::STATUS_NOT_FOUND);
            return $res;
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
                . ' <a class="lined" href="https://github.com/grosjo/nextcloud-grauphel/issues">'
                . 'github.com/grosjo/nextcloud-grauphel/issues</a>'
                . ' and attach the XML version of the note.'
                . '</div>';
        }

        $res->setParams(
            array(
                'note' => $note,
                'note-content' => $contentHtml,
                'links' => array(
                    'html' => $this->urlGen->linkToRoute(
                        'grauphel.notes.html', array('guid' => $guid)
                    ),
                    'json' => $this->urlGen->linkToRoute(
                        'grauphel.api.note', array(
                            'guid' => $guid, 'username' => $this->user->getUID()
                        )
                    ),
                    'text' => $this->urlGen->linkToRoute(
                        'grauphel.notes.text', array('guid' => $guid)
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
        $this->addGlobalVars($res);
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function tag($rawtag)
    {
        $rawtag = $this->unescapeTagFromUrl($rawtag);
        $notes = $this->getNotes()->loadNotesOverview(null, $rawtag, true);

        switch ($this->request->getParam('sortby', 'title')) {
        case 'title':
            usort(
                $notes,
                function($noteA, $noteB) {
                    return strcasecmp($noteA['title'], $noteB['title']);
                }
            );
            break;
        case 'date':
            usort(
                $notes,
                function($noteA, $noteB) {
                    return strcmp($noteB['last-change-date'], $noteA['last-change-date']);
                }
            );
            break;
        }

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
                'tagUrl' => $this->urlGen->linkToRoute(
                    'grauphel.gui.tag',
                    array('rawtag' => $this->escapeTagForUrl($rawtag))
                ),
            )
        );
        $this->addGlobalVars($res);
        $this->addNavigation($res, $rawtag);

        return $res;
    }

    /**
     * Show access tokens
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function tokens()
    {
        $res = new TemplateResponse('grauphel', 'tokens');
        $res->setParams(
            array(
                'tokens' => $this->tokens->loadForUser(
                    $this->user->getUID(), 'access'
                ),
                'client' => new \OCA\Grauphel\Auth\Client(),
                'username' => $this->user->getUID(),
            )
        );
        $this->addGlobalVars($res);
        $this->addNavigation($res, null);

        return $res;
    }

    /**
     * Allow the user to clear his database
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function database($reset = null)
    {
        $res = new TemplateResponse('grauphel', 'gui-database');
        $res->setParams(array('reset' => $reset));
        $this->addGlobalVars($res);
        $this->addNavigation($res, null);
        $this->addStats($res);

        return $res;
    }

    /**
     * Resets the database by deleting all notes and deleting the user's
     * sync data.
     */
    #[NoAdminRequired]
    public function databaseReset()
    {
        $reset = false;
        $username = (string) $this->request->getParam('username', '');
        if ($username != '' && $username == $this->user->getUID()) {
            $notes = $this->getNotes();
            $notes->deleteAll();
            $notes->deleteSyncData();
            $reset = true;
        }

        return $this->database($reset);
    }

    /**
     * Register some variables that templates will probably need.
     *
     * @return void
     */
    protected function addGlobalVars(TemplateResponse $res)
    {
        $params = $res->getParams();
        $params['date']   = $this->dateFormatter;
        $params['urlGen'] = $this->urlGen;
        $res->setParams($params);
    }

    protected function addNavigation(TemplateResponse $res, $selectedRawtag = null)
    {
        $tags = array();
        if ($this->user !== null) {
            $rawtags = $this->getNotes()->getTags();
            sort($rawtags);
            array_unshift(
                $rawtags,
                'grauphel:special:all', 'grauphel:special:untagged'
            );

            foreach ($rawtags as $rawtag) {
                $name = $this->getPrettyTagName($rawtag);
                if ($name !== false) {
                    $tags[] = array(
                        'name' => $name,
                        'id'   => $rawtag,
                        'href' => $this->urlGen->linkToRoute(
                            'grauphel.gui.tag',
                            array('rawtag' => $this->escapeTagForUrl($rawtag))
                        ),
                        'selected' => $rawtag == $selectedRawtag,
                    );
                }
            }
        }

        $params = $res->getParams();
        $params['appNavigation'] = $this->renderPart(
            'appnavigation',
            array(
                'apiroot'  => $this->getApiRootUrl(),
                'tags'     => $tags,
                'urlGen'   => $this->urlGen,
                'loggedIn' => $this->user !== null,
            )
        );
        $res->setParams($params);
    }

    protected function addStats(TemplateResponse $res)
    {
        if ($this->user === null) {
            return;
        }

        $username = $this->user->getUID();
        $notes  = $this->getNotes();
        $params = $res->getParams();
        $params['stats'] = $this->renderPart(
            'indexStats',
            array(
                'urlGen'  => $this->urlGen,
                'notes'   => count($notes->loadNotesOverview()),
                'syncrev' => $notes->loadSyncData()->latestSyncRevision,
                'tokens'  => count($this->tokens->loadForUser($username, 'access')),
            )
        );
        $res->setParams($params);
    }

    /**
     * Render a partial template into a HTML string
     *
     * @param string $name   Template name
     * @param array  $params Template variables
     *
     * @return string HTML
     */
    protected function renderPart($name, $params)
    {
        $part = new TemplateResponse(
            'grauphel', $name, $params, TemplateResponse::RENDER_AS_BLANK
        );
        return $part->render();
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
        $this->notes->setUsername($this->user->getUID());
        return $this->notes;
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

    protected function escapeTagForUrl($rawtag)
    {
        return str_replace('/', '%2F', $rawtag);
    }

    protected function unescapeTagFromUrl($rawtag)
    {
        return str_replace('%2F', '/', $rawtag);
    }
}
