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
        $this->checkDeps();

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
     * Show all notes of a tag
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function tag($rawtag)
    {
        $notes = $this->getNotes()->loadNotesOverview(null, $rawtag);

        $res = new TemplateResponse('grauphel', 'tag');
        $res->setParams(
            array(
                'tag'    => substr($rawtag, 16),
                'rawtag' => $rawtag,
                'notes'  => $notes,
            )
        );
        $this->addNavigation($res, $rawtag);

        return $res;
    }

    protected function addNavigation(TemplateResponse $res, $selectedRawtag = null)
    {
        $nav = new \OCP\Template('grauphel', 'appnavigation', '');
        $nav->assign('apiroot', $this->getApiRootUrl());

        $params = $res->getParams();
        $params['appNavigation'] = $nav;
        $res->setParams($params);

        if ($this->user === null) {
            return;
        }

        $rawtags = $this->getNotes()->getTags();
        sort($rawtags);
        $tags = array();
        foreach ($rawtags as $rawtag) {
            if (substr($rawtag, 0, 16) == 'system:notebook:') {
                $tags[] = array(
                    'name' => substr($rawtag, 16),
                    'id'   => $rawtag,
                    'href' => $this->urlGen->linkToRoute(
                        'grauphel.gui.tag', array('rawtag' => $rawtag)
                    ),
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
            throw new \Exception('PHP extension "oauth" is required');
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
}
?>
