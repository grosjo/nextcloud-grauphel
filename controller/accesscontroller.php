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
 * Login and authorization handling
 *
 * @category  Tools
 * @package   Grauphel
 * @author    Christian Weiske <cweiske@cweiske.de>
 * @copyright 2014 Christian Weiske
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version   Release: @package_version@
 * @link      http://cweiske.de/grauphel.htm
 */
class AccessController extends Controller
{
    public function login($returnUrl = null)
    {
        $returnUrl = $this->loadReturnUrl($returnUrl);

        if (isset($_POST['user']) && trim($_POST['user']) != '') {
            $this->deps->frontend->setUser(trim($_POST['user']));
            header('Location: ' . $returnUrl);
            exit(0);
        }

        $hFormUrl = htmlspecialchars(
            $this->deps->urlGen->addParams(
                $this->deps->urlGen->accessLogin(),
                array('returnurl' => $returnUrl)
            )
        );
        //FIXME: do some real login
        header('HTTP/1.0 200 OK');

        echo <<<HTM
<html>
 <head>
  <title>grauphel login</title>
 </head>
 <body>
  <form method="post" action="$hFormUrl">
   <p>
    Log into <em>grauphel</em>:
   </p>
   <label>
    User name:
    <input id="user" type="text" name="user" size="20" value=""/>
   </label>
   <input type="submit" value="Login" />
  </form>
  <script type="text/javascript">
//FIXME
/*
document.getElementById('user').value = 'cweiske';
document.forms[0].submit();
/**/
  </script>
 </body>
</html>
HTM;
        exit(0);
    }

    public function authorize($returnUrl = null)
    {
        var_dump('asd');die();
        $returnUrl = $this->loadReturnUrl($returnUrl);

        if (isset($_POST['auth'])) {
            if ($_POST['auth'] == 'ok') {
                $this->deps->frontend->setAuth(true);
            } else if ($_POST['auth'] == 'cancel') {
                $this->deps->frontend->setAuth(false);
            }
            header('Location: ' . $returnUrl);
            exit(0);
        }

        header('HTTP/1.0 200 OK');
        $hFormUrl = htmlspecialchars(
            $this->deps->urlGen->addParams(
                $this->deps->urlGen->accessAuthorize(),
                array('returnurl' => $returnUrl)
            )
        );

        echo <<<HTM
<html>
 <head>
  <title>grauphel authorization</title>
 </head>
 <body>
  <form method="post" action="$hFormUrl">
   <p>
    Shall application FIXME get full access to the notes?
   </p>
   <button type="submit" name="auth" value="ok">Yes, authorize</button>
   <button type="submit" name="auth" value="cancel">No, decline</button>
 </body>
</html>
HTM;
        exit(0);
    }

    protected function loadReturnUrl($returnUrl = null)
    {
        if ($returnUrl === null) {
            if (isset($_GET['returnurl'])) {
                $returnUrl = $_GET['returnurl'];
            } else {
                $returnUrl = $this->deps->urlGen->index();
            }
        }
        return $returnUrl;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function test()
    {
        var_dump('asd');die();
        $this->registerResponder('xml', function($value) {
                return new XMLResponse($value);
            });
        return array('foo' => 'bar');
    }
}
?>
