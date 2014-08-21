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
use \OCP\AppFramework\Http\JSONResponse;

use \OCA\Grauphel\Lib\NoteStorage;
use \OCA\Grauphel\Lib\OAuth;
use \OCA\Grauphel\Lib\OAuthException;
use \OCA\Grauphel\Lib\Dependencies;
use \OCA\Grauphel\Lib\Response\ErrorResponse;

/**
 * Tomboy's REST API
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class ApiController extends Controller
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
        $this->user  = $user;
        $this->deps  = Dependencies::get();
        $this->notes = new NoteStorage($this->deps->urlGen);

        //default http header: we assume something is broken
        header('HTTP/1.0 500 Internal Server Error');
    }

    /**
     * /api/1.0
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function index($route = 'grauphel.api.index')
    {
        $deps = Dependencies::get();
        $authenticated = false;
        $oauth = new OAuth();
        $oauth->setDeps($deps);
        $urlGen = $deps->urlGen;

        try {
            $provider = OAuth::getProvider();
            $oauth->registerHandler($provider)
                ->registerAccessTokenHandler($provider);
            $provider->checkOAuthRequest(
                $urlGen->getAbsoluteURL(
                    $urlGen->linkToRoute($route)
                )
            );
            $authenticated = true;
            $token = $deps->tokens->load('access', $provider->token);
            $username = $token->user;

        } catch (OAuthException $e) {
            return new ErrorResponse($e->getMessage());
        } catch (\OAuthException $e) {
            if ($e->getCode() != OAUTH_PARAMETER_ABSENT) {
                $oauth->error($e);
            }
            if ($this->user !== null) {
                $username = $this->user->getUID();
                $authenticated = true;
            }
        }

        $data = array(
            'oauth_request_token_url' => $urlGen->getAbsoluteURL(
                $urlGen->linkToRoute('grauphel.oauth.requestToken')
            ),
            'oauth_authorize_url'     => $urlGen->getAbsoluteURL(
                $urlGen->linkToRoute('grauphel.oauth.authorize')
            ),
            'oauth_access_token_url'  => $urlGen->getAbsoluteURL(
                $urlGen->linkToRoute('grauphel.oauth.accessToken')
            ),
            'api-version' => '1.0',
        );

        if ($authenticated) {
            $data['user-ref'] = array(
                'api-ref' => $urlGen->getAbsoluteURL(
                    $urlGen->linkToRoute(
                        'grauphel.api.user', array('username' => $username)
                    )
                ),
                'href' => null,//FIXME
            );
        }

        return new JSONResponse($data);
    }

    /**
     * /api/1.0/
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function indexSlash()
    {
        return $this->index('grauphel.api.indexSlash');
    }

    /**
     * GET /api/1.0/$user
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function user($username)
    {
        $this->verifyUser(
            $username,
            $this->deps->urlGen->getAbsoluteURL(
                $this->deps->urlGen->linkToRoute(
                    'grauphel.api.user', array('username' => $username)
                )
            )
        );
        $syncdata = $this->notes->loadSyncData($username);

        $data = array(
            'user-name'  => $username,
            'first-name' => null,
            'last-name'  => null,
            'notes-ref'  => array(
                'api-ref' => $this->deps->urlGen->getAbsoluteURL(
                    $this->deps->urlGen->linkToRoute(
                        'grauphel.api.notes', array('username' => $username)
                    )
                ),
                'href'    => null,
            ),
            'latest-sync-revision' => $syncdata->latestSyncRevision,
            'current-sync-guid'    => $syncdata->currentSyncGuid,
        );
        return new JSONResponse($data);
    }

    /**
     * GET /api/1.0/$user/notes
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function notes($username)
    {
        $this->verifyUser(
            $username,
            $this->deps->urlGen->getAbsoluteURL(
                $this->deps->urlGen->linkToRoute(
                    'grauphel.api.notes', array('username' => $username)
                )
            )
        );
        $syncdata = $this->notes->loadSyncData($username);
        return $this->fetchNotes($username, $syncdata);
    }

    /**
     * PUT /api/1.0/$user/notes
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function notesSave($username)
    {
        $this->verifyUser(
            $username,
            $this->deps->urlGen->getAbsoluteURL(
                $this->deps->urlGen->linkToRoute(
                    'grauphel.api.notesSave', array('username' => $username)
                )
            )
        );
        $syncdata = $this->notes->loadSyncData($username);
        
        $res = $this->handleNoteSave($username, $syncdata);
        if ($res instanceof \OCP\AppFramework\Http\Response) {
            return $res;
        }

        return $this->fetchNotes($username, $syncdata);
    }

    protected function fetchNotes($username, $syncdata)
    {
        $since = null;
        if (isset($_GET['since'])) {
            $since = (int) $_GET['since'];
        }

        if (isset($_GET['include_notes']) && $_GET['include_notes']) {
            $notes = $this->notes->loadNotesFull($username, $since);
        } else {
            $notes = $this->notes->loadNotesOverview($username, $since);
        }

        //work around bug https://bugzilla.gnome.org/show_bug.cgi?id=734313
        foreach ($notes as $note) {
            if (isset($note->{'note-content-version'})) {
                $note->{'note-content-version'} = 0.3;
            }
        }

        $data = array(
            'latest-sync-revision' => $syncdata->latestSyncRevision,
            'notes' => $notes,
        );
        return new JSONResponse($data);
    }

    protected function handleNoteSave($username, $syncdata)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'PUT') {
            return;
        }

        //note that we have more data in $arPut than just our JSON
        // request object merges it with other data
        $arPut = $this->request->put;

        //structural validation
        if (!isset($arPut['latest-sync-revision'])) {
            return new ErrorResponse('Missing "latest-sync-revision"');
        }
        if (!isset($arPut['note-changes'])) {
            return new ErrorResponse('Missing "note-changes"');
        }
        foreach ($arPut['note-changes'] as $note) {
            //owncloud converts object to array, so we reverse
            $note = (object) $note;
            if (!isset($note->guid) || $note->guid == '') {
                return new ErrorResponse('Missing "guid" on note');
            }
        }

        //content validation
        if ($arPut['latest-sync-revision'] != $syncdata->latestSyncRevision +1
            && $syncdata->latestSyncRevision != -1
        ) {
            return new ErrorResponse(
                'Wrong "latest-sync-revision". You are not up to date.'
            );
        }

        //update
        ++$syncdata->latestSyncRevision;
        foreach ($arPut['note-changes'] as $noteUpdate) {
            //owncloud converts object to array, so we reverse
            $noteUpdate = (object) $noteUpdate;

            $note = $this->notes->load($username, $noteUpdate->guid);
            if (isset($noteUpdate->command) && $noteUpdate->command == 'delete') {
                $this->notes->delete($username, $noteUpdate->guid);
            } else {
                $this->notes->update(
                    $note, $noteUpdate, $syncdata->latestSyncRevision
                );
                $this->notes->save($username, $note);
            }
        }

        $this->notes->saveSyncData($username, $syncdata);
    }

    /**
     * GET /api/1.0/$user/notes/$noteguid
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function note()
    {
        //FIXME
        $deps = Dependencies::get();
        $username = $deps->urlGen->loadUsername();
        $guid     = $deps->urlGen->loadGuid();
        $oauth = new \OAuth();
        $oauth->setDeps($deps);
        $oauth->verifyOAuthUser($username, $deps->urlGen->note($username, $guid));

        $note = $this->notes->load($username, $guid, false);
        if ($note === null) {
            header('HTTP/1.0 404 Not Found');
            header('Content-type: text/plain');
            echo "Note does not exist\n";
            exit(1);
        }

        $data = array('note' => array($note));
        $deps->renderer->sendJson($data);
    }

    /**
     * Checks if the given user is authorized (by oauth token or normal login)
     *
     * @param string $username Username to verify
     *
     * @return boolean True if all is fine, Response in case of an error
     */
    protected function verifyUser($username, $curUrl)
    {
        if ($this->user !== null && $this->user->getUID() == $username) {
            return true;
        }

        $oauth = new OAuth();
        $oauth->setDeps($this->deps);
        $oauth->verifyOAuthUser($username, $curUrl);
    }
}
?>
