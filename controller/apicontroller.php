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
     */
    public function index()
    {
        var_dump('asd');die();
        $authenticated = false;
        $oauth = new OAuth();
        $oauth->setDeps($this->deps);
        $urlGen = $this->deps->urlGen;

        try {
            $provider = new \OAuthProvider();
            $oauth->registerHandler($provider)
                ->registerAccessTokenHandler($provider);
            $provider->checkOAuthRequest($urlGen->fullPath());
            $authenticated = true;
            $token = $this->deps->tokens->load('access', $provider->token);
            $username = $token->user;

        } catch (OAuth_Exception $e) {
            $this->deps->renderer->errorOut($e->getMessage());
        } catch (\OAuthException $e) {
            if ($e->getCode() != OAUTH_PARAMETER_ABSENT) {
                $oauth->error($e);
            }
        }

        $data = array(
            'oauth_request_token_url' => $urlGen->oauthRequestToken(),
            'oauth_authorize_url'     => $urlGen->oauthAuthorize(),
            'oauth_access_token_url'  => $urlGen->oauthAccessToken(),
            'api-version' => '1.0',
        );

        if ($authenticated) {
            $data['user-ref'] = array(
                'api-ref' => $urlGen->user($username),
                'href'    => $urlGen->userHtml($username),
            );
        }

        $this->deps->renderer->sendJson($data);
    }

    /**
     * GET /api/1.0/$user/notes/$noteguid
     */
    public function note()
    {
        $username = $this->deps->urlGen->loadUsername();
        $guid     = $this->deps->urlGen->loadGuid();
        $oauth = new OAuth();
        $oauth->setDeps($this->deps);
        $oauth->verifyOAuthUser($username, $this->deps->urlGen->note($username, $guid));

        $note = $this->deps->notes->load($username, $guid, false);
        if ($note === null) {
            header('HTTP/1.0 404 Not Found');
            header('Content-type: text/plain');
            echo "Note does not exist\n";
            exit(1);
        }

        $data = array('note' => array($note));
        $this->deps->renderer->sendJson($data);
    }

    /**
     * GET|PUT /api/1.0/$user/notes
     */
    public function notes()
    {
        $username = $this->deps->urlGen->loadUsername();
        $oauth = new OAuth();
        $oauth->setDeps($this->deps);
        $oauth->verifyOAuthUser($username, $this->deps->urlGen->notes($username));

        $syncdata = $this->deps->notes->loadSyncData($username);

        $this->handleNoteSave($username, $syncdata);

        $since = null;
        if (isset($_GET['since'])) {
            $since = (int) $_GET['since'];
        }

        if (isset($_GET['include_notes']) && $_GET['include_notes']) {
            $notes = $this->deps->notes->loadNotesFull($username, $since);
        } else {
            $notes = $this->deps->notes->loadNotesOverview($username, $since);
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
        $this->deps->renderer->sendJson($data);
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
        ++$syncdata->latestSyncRevision;
        foreach ($putObj->{'note-changes'} as $noteUpdate) {
            $note = $this->deps->notes->load($username, $noteUpdate->guid);
            if (isset($noteUpdate->command) && $noteUpdate->command == 'delete') {
                $this->deps->notes->delete($username, $noteUpdate->guid);
            } else {
                $this->deps->notes->update(
                    $note, $noteUpdate, $syncdata->latestSyncRevision
                );
                $this->deps->notes->save($username, $note);
            }
        }

        $this->deps->notes->saveSyncData($username, $syncdata);
    }

    /**
     * GET /api/1.0/$user
     */
    public function user()
    {
        $username = $this->deps->urlGen->loadUsername();

        $oauth = new OAuth();
        $oauth->setDeps($this->deps);
        $oauth->verifyOAuthUser($username, $this->deps->urlGen->user($username));

        $syncdata = $this->deps->notes->loadSyncData($username);

        $data = array(
            'user-name'  => $username,
            'first-name' => null,
            'last-name'  => null,
            'notes-ref'  => array(
                'api-ref' => $this->deps->urlGen->notes($username),
                'href'    => null,
            ),
            'latest-sync-revision' => $syncdata->latestSyncRevision,
            'current-sync-guid'    => $syncdata->currentSyncGuid,
        );
        $this->deps->renderer->sendJson($data);
    }
}
?>
