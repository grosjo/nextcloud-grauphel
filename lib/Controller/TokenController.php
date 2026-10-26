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
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\IRequest;
use \OCP\IURLGenerator;
use \OCP\IUserSession;
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
    protected $user;
    protected IURLGenerator $urlGen;
    protected TokenStorage $tokens;

    /**
     * constructor of the controller
     *
     * @param string   $appName Name of the app
     * @param IRequest $request Instance of the request
     */
    public function __construct(
        $appName, IRequest $request, IUserSession $userSession,
        IURLGenerator $urlGen, TokenStorage $tokens
    ) {
        parent::__construct($appName, $request);
        $this->user   = $userSession->getUser();
        $this->urlGen = $urlGen;
        $this->tokens = $tokens;
    }

    /**
     * Delete an access token
     * DELETE /tokens/$username/$tokenKey
     */
    #[NoAdminRequired]
    public function delete($username, $tokenKey)
    {
        if ($this->user === null || $this->user->getUID() != $username) {
            $res = new ErrorResponse('You may only delete your own tokens.');
            $res->setStatus(Http::STATUS_FORBIDDEN);
            return $res;
        }

        try {
            $token = $this->tokens->load('access', $tokenKey);
        } catch (OAuthException $e) {
            $res = new ErrorResponse('Token not found.');
            $res->setStatus(Http::STATUS_NOT_FOUND);
            return $res;
        }

        if ($username != $token->user) {
            $res = new ErrorResponse('You may only delete your own tokens.');
            $res->setStatus(Http::STATUS_FORBIDDEN);
            return $res;
        }

        $this->tokens->delete('access', $tokenKey);

        $res = new Http\Response();
        $res->setStatus(Http::STATUS_NO_CONTENT);
        return $res;
    }

    /**
     * Delete an access token via POST
     * POST /tokens/$username/$tokenKey
     */
    #[NoAdminRequired]
    public function deletePost($username, $tokenKey)
    {
        if ($this->request->getParam('delete') == 1) {
            $this->delete($username, $tokenKey);
        }

        $res = new RedirectResponse(
            $this->urlGen->getAbsoluteURL(
                $this->urlGen->linkToRoute('grauphel.gui.tokens')
            )
        );
        $res->setStatus(Http::STATUS_FOUND);
        return $res;
    }
}
