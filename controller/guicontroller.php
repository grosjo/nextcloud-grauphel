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
        $res->setParams(array('apiurl' => $this->getApiUrl()));
        $this->addNavigation($res);
        return $res;
    }

    protected function addNavigation(TemplateResponse $res)
    {
        $nav = new \OCP\Template('grauphel', 'appnavigation', '');
        $nav->assign('apiurl', $this->getApiUrl());

        $params = $res->getParams();
        $params['appNavigation'] = $nav;
        $res->setParams($params);
    }

    protected function checkDeps()
    {
        if (!class_exists('OAuthProvider')) {
            throw new \Exception('PHP extension "oauth" is required');
        }
    }

    protected function getApiUrl()
    {
        //we need to remove the trailing / for tomdroid and conboy
        return rtrim(
            $this->urlGen->getAbsoluteURL(
                $this->urlGen->linkToRoute('grauphel.gui.index')
            ),
            '/'
        );
    }
}
?>
