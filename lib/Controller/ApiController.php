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
use \OCP\AppFramework\Http;
use \OCP\AppFramework\Http\Attribute\NoAdminRequired;
use \OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use \OCP\AppFramework\Http\Attribute\PublicPage;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\IDBConnection;
use \OCP\IRequest;
use \OCP\IURLGenerator;
use \OCP\IUserSession;

use \OCA\Grauphel\Auth\Client;
use \OCA\Grauphel\Storage\NoteStorage;
use \OCA\Grauphel\Auth\OAuth;
use \OCA\Grauphel\Auth\OAuthException;
use \OCA\Grauphel\Response\ErrorResponse;

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
    protected $user;
    protected IURLGenerator $urlGen;
    protected IDBConnection $db;
    protected NoteStorage $notes;
    protected OAuth $oauth;

    /**
     * constructor of the controller
     *
     * @param string   $appName Name of the app
     * @param IRequest $request Instance of the request
     */
    public function __construct(
        $appName, IRequest $request, IUserSession $userSession,
        IURLGenerator $urlGen, IDBConnection $db,
        NoteStorage $notes, OAuth $oauth
    ) {
        parent::__construct($appName, $request);
        $this->user   = $userSession->getUser();
        $this->urlGen = $urlGen;
        $this->db     = $db;
        $this->notes  = $notes;
        $this->oauth  = $oauth;
    }

    /**
     * /api/1.0
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function index($route = 'grauphel.api.index')
    {
        $authenticated = false;
        $urlGen = $this->urlGen;

        try {
            $token = $this->oauth->checkApiRequest(
                $urlGen->getAbsoluteURL(
                    $urlGen->linkToRoute($route)
                )
            );
            $authenticated = true;
            $username = $token->user;
        } catch (OAuthException $e) {
            if ($e->problem != OAuthException::PARAMETER_ABSENT) {
                return $this->oauth->errorResponse($e);
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

        $cl = new Client();
        $client = $cl->getClient($this->request->getHeader('X-Tomboy-Client'));
        if ($client !== false) {
            $data['oauth_authorize_url'] .= '?client=' . urlencode($client);
        }

        if ($authenticated) {
            $data['user-ref'] = array(
                'api-ref' => $urlGen->getAbsoluteURL(
                    $urlGen->linkToRoute(
                        'grauphel.api.user', array('username' => $username)
                    )
                ),
                'href' => null,
            );
        }

        return new JSONResponse($data);
    }

    /**
     * /api/1.0/
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function indexSlash()
    {
        return $this->index('grauphel.api.indexSlash');
    }

    /**
     * GET /api/1.0/$user
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function user($username)
    {
        $res = $this->verifyUser(
            $username,
            $this->urlGen->getAbsoluteURL(
                $this->urlGen->linkToRoute(
                    'grauphel.api.user', array('username' => $username)
                )
            )
        );
        if ($res !== null) {
            return $res;
        }
        $syncdata = $this->notes->loadSyncData();

        $data = array(
            'user-name'  => $username,
            'first-name' => null,
            'last-name'  => null,
            'notes-ref'  => array(
                'api-ref' => $this->urlGen->getAbsoluteURL(
                    $this->urlGen->linkToRoute(
                        'grauphel.api.notes', array('username' => $username)
                    )
                ),
                'href'    => $this->urlGen->getAbsoluteURL(
                    $this->urlGen->linkToRoute('grauphel.gui.index')
                ),
            ),
            'latest-sync-revision' => $syncdata->latestSyncRevision,
            'current-sync-guid'    => $syncdata->currentSyncGuid,
        );
        return new JSONResponse($data);
    }

    /**
     * GET /api/1.0/$user/notes
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function notes($username)
    {
        $res = $this->verifyUser(
            $username,
            $this->urlGen->getAbsoluteURL(
                $this->urlGen->linkToRoute(
                    'grauphel.api.notes', array('username' => $username)
                )
            )
        );
        if ($res !== null) {
            return $res;
        }
        $syncdata = $this->notes->loadSyncData();
        return $this->fetchNotes($syncdata);
    }

    /**
     * PUT /api/1.0/$user/notes
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function notesSave($username)
    {
        $res = $this->verifyUser(
            $username,
            $this->urlGen->getAbsoluteURL(
                $this->urlGen->linkToRoute(
                    'grauphel.api.notesSave', array('username' => $username)
                )
            )
        );
        if ($res !== null) {
            return $res;
        }
        $syncdata = $this->notes->loadSyncData();

        $res = $this->handleNoteSave($username, $syncdata);
        if ($res instanceof \OCP\AppFramework\Http\Response) {
            return $res;
        }

        return $this->fetchNotes($syncdata);
    }

    protected function fetchNotes($syncdata)
    {
        $since = $this->request->getParam('since');
        if ($since !== null) {
            $since = (int) $since;
        }

        if ($this->request->getParam('include_notes')) {
            $notes = $this->notes->loadNotesFull($since);
        } else {
            $notes = $this->notes->loadNotesOverview($since);
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
        if ($this->request->getMethod() != 'PUT') {
            return;
        }

        //Note that we have more data in $arPut than just our JSON.
        // The request object merges it with other data.
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
        $this->db->beginTransaction();
        try {
            ++$syncdata->latestSyncRevision;
            foreach ($arPut['note-changes'] as $noteUpdate) {
                //owncloud converts object to array, so we reverse
                $noteUpdate = (object) $noteUpdate;

                $note = $this->notes->load($noteUpdate->guid);
                if (isset($noteUpdate->command) && $noteUpdate->command == 'delete') {
                    $this->notes->delete($noteUpdate->guid);
                } else {
                    $this->notes->update(
                        $note, $noteUpdate, $syncdata->latestSyncRevision
                    );
                    $this->notes->save($note);
                }
            }

            $this->notes->saveSyncData($syncdata);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * GET /api/1.0/$user/notes/$noteguid
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[PublicPage]
    public function note($username, $guid)
    {
        $res = $this->verifyUser(
            $username,
            $this->urlGen->getAbsoluteURL(
                $this->urlGen->linkToRoute(
                    'grauphel.api.note',
                    array('username' => $username, 'guid' => $guid)
                )
            )
        );
        if ($res !== null) {
            return $res;
        }

        $note = $this->notes->load($guid, false);
        if ($note === null) {
            $res = new ErrorResponse('Note does not exist');
            $res->setStatus(Http::STATUS_NOT_FOUND);
            return $res;
        }

        return new JSONResponse($note);
    }

    /**
     * Checks if the given user is authorized (by oauth token or normal login)
     *
     * @param string $username Username to verify
     * @param string $curUrl   Absolute URL of the current API endpoint
     *
     * @return Http\Response|null NULL if all is fine, Response in case of an error
     */
    protected function verifyUser($username, $curUrl)
    {
        if ($this->user !== null && $this->user->getUID() == $username) {
            $this->notes->setUsername($username);
            return null;
        }

        try {
            $token = $this->oauth->checkApiRequest($curUrl);
        } catch (OAuthException $e) {
            return $this->oauth->errorResponse($e);
        }
        if ($token->user != $username) {
            $res = new ErrorResponse('Invalid user');
            $res->setStatus(Http::STATUS_FORBIDDEN);
            return $res;
        }

        $this->notes->setUsername($username);
        return null;
    }
}
