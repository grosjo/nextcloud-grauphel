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
use \OCP\AppFramework\Http\RedirectResponse;
use \OCA\Grauphel\Tools\Dependencies;
use \OCA\Grauphel\Auth\OAuthException;
use \OCA\Grauphel\Response\ErrorResponse;
use \OCA\Grauphel\Storage\TokenStorage;

/**
 * OAuth token management
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class TokenController extends Controller
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
        $this->user = $user;
        $this->deps = Dependencies::get();

        //default http header: we assume something is broken
        header('HTTP/1.0 500 Internal Server Error');
    }


    /**
     * Delete an access token
     * DELETE /tokens/$username/$tokenKey
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function delete($username, $tokenKey)
    {
        if (false && ($this->user === null || $this->user->getUid() != $username)) {
            $res = new ErrorResponse('You may only delete your own tokens.');
            $res->setStatus(\OCP\AppFramework\Http::STATUS_FORBIDDEN);
            return $res;
        }

        $deps = Dependencies::get();
        try {
            $token = $deps->tokens->load('access', $tokenKey);
        } catch (OAuthException $e) {
            $res = new ErrorResponse('Token not found.');
            $res->setStatus(\OCP\AppFramework\Http::STATUS_NOT_FOUND);
            return $res;
        }

        if ($username != $token->user) {
            $res = new ErrorResponse('You may only delete your own tokens.');
            $res->setStatus(\OCP\AppFramework\Http::STATUS_FORBIDDEN);
            return $res;
        }

        $deps->tokens->delete('access', $tokenKey);

        $res = new \OCP\AppFramework\Http\Response();
        $res->setStatus(\OCP\AppFramework\Http::STATUS_NO_CONTENT);
        return $res;
    }

    /**
     * Delete an access token via POST
     * POST /tokens/$username/$tokenKey
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deletePost($username, $tokenKey)
    {
        if (isset($_POST['delete']) && $_POST['delete'] == 1) {
            $this->delete($username, $tokenKey);
        }

        $res = new RedirectResponse(
            $this->deps->urlGen->getAbsoluteURL(
                $this->deps->urlGen->linkToRoute('grauphel.gui.tokens')
            )
        );
        $res->setStatus(\OCP\AppFramework\Http::STATUS_FOUND);
        return $res;
    }
}
?>
