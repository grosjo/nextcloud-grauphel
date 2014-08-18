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

use \OCA\Grauphel\Lib\OAuth;
use \OCA\Grauphel\Lib\Dependencies;

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
     * /api/1.0
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function index()
    {
        $deps = Dependencies::get();
        $authenticated = false;
        $oauth = new OAuth();
        $oauth->setDeps($deps);
        $urlGen = $deps->urlGen;

        try {
            $provider = new \OAuthProvider();
            $oauth->registerHandler($provider)
                ->registerAccessTokenHandler($provider);
            $provider->checkOAuthRequest(
                $urlGen->getAbsoluteURL(
                    $urlGen->linkToRoute('grauphel.api.index')
                )
            );
            $authenticated = true;
            $token = $deps->tokens->load('access', $provider->token);
            $username = $token->user;

        } catch (\OAuth_Exception $e) {
            $deps->renderer->errorOut($e->getMessage());
        } catch (\OAuthException $e) {
            if ($e->getCode() != OAUTH_PARAMETER_ABSENT) {
                $oauth->error($e);
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
                        'grauphel.api.user', array('user' => $username)
                    )
                ),
                'href' => null,//FIXME
            );
        }

        return new JSONResponse($data);
        $deps->renderer->sendJson($data);
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
        $deps = Dependencies::get();
        $username = $deps->urlGen->loadUsername();
        $guid     = $deps->urlGen->loadGuid();
        $oauth = new \OAuth();
        $oauth->setDeps($deps);
        $oauth->verifyOAuthUser($username, $deps->urlGen->note($username, $guid));

        $note = $deps->notes->load($username, $guid, false);
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
     * GET|PUT /api/1.0/$user/notes
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function notes()
    {
        $deps = Dependencies::get();
        $username = $deps->urlGen->loadUsername();
        $oauth = new \OAuth();
        $oauth->setDeps($deps);
        $oauth->verifyOAuthUser($username, $deps->urlGen->notes($username));

        $syncdata = $deps->notes->loadSyncData($username);

        $this->handleNoteSave($username, $syncdata);

        $since = null;
        if (isset($_GET['since'])) {
            $since = (int) $_GET['since'];
        }

        if (isset($_GET['include_notes']) && $_GET['include_notes']) {
            $notes = $deps->notes->loadNotesFull($username, $since);
        } else {
            $notes = $deps->notes->loadNotesOverview($username, $since);
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
        $deps->renderer->sendJson($data);
    }

    protected function handleNoteSave($username, $syncdata)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'PUT') {
            return;
        }

        $data = file_get_contents('php://input');
        $putObj = json_decode($data);
        if ($putObj === NULL) {
            errorOut('Invalid JSON data in PUT request');
        }

        //structural validation
        if (!isset($putObj->{'latest-sync-revision'})) {
            errorOut('Missing "latest-sync-revision"');
        }
        if (!isset($putObj->{'note-changes'})) {
            errorOut('Missing "note-changes"');
        }
        foreach ($putObj->{'note-changes'} as $note) {
            if (!isset($note->guid) || $note->guid == '') {
                errorOut('Missing "guid" on note');
            }
        }

        //content validation
        if ($putObj->{'latest-sync-revision'} != $syncdata->latestSyncRevision +1
            && $syncdata->latestSyncRevision != -1
        ) {
            errorOut('Wrong "latest-sync-revision". You are not up to date.');
        }

        //update
        $deps = Dependencies::get();
        ++$syncdata->latestSyncRevision;
        foreach ($putObj->{'note-changes'} as $noteUpdate) {
            $note = $deps->notes->load($username, $noteUpdate->guid);
            if (isset($noteUpdate->command) && $noteUpdate->command == 'delete') {
                $deps->notes->delete($username, $noteUpdate->guid);
            } else {
                $deps->notes->update(
                    $note, $noteUpdate, $syncdata->latestSyncRevision
                );
                $deps->notes->save($username, $note);
            }
        }

        $deps->notes->saveSyncData($username, $syncdata);
    }

    /**
     * GET /api/1.0/$user
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function user()
    {
        $deps = Dependencies::get();
        $username = $deps->urlGen->loadUsername();

        $oauth = new \OAuth();
        $oauth->setDeps($deps);
        $oauth->verifyOAuthUser($username, $deps->urlGen->user($username));

        $syncdata = $deps->notes->loadSyncData($username);

        $data = array(
            'user-name'  => $username,
            'first-name' => null,
            'last-name'  => null,
            'notes-ref'  => array(
                'api-ref' => $deps->urlGen->notes($username),
                'href'    => null,
            ),
            'latest-sync-revision' => $syncdata->latestSyncRevision,
            'current-sync-guid'    => $syncdata->currentSyncGuid,
        );
        $deps->renderer->sendJson($data);
    }
}
?>
