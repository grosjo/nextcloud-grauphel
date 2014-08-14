<?php
namespace OCA\Grauphel\AppInfo;
use \OCP\AppFramework\App;

class Application extends App
{
    public function __construct(array $urlParams=array())
    {
        parent::__construct('grauphel', $urlParams);

        $container = $this->getContainer();

        /**
         * Controllers
         */
        $container->registerService(
           'ApiController',
            function($c) {
                return new \OCA\Grauphel\Controller\ApiController(
                    $c->query('AppName'),
                    $c->query('Request')
                );
            }
        );
        $container->registerService(
           'AccessController',
            function($c) {
                 return new \OCA\Grauphel\Controller\AccessController(
                    $c->query('AppName'),
                    $c->query('Request')
                );
            }
        );
        $container->registerService(
           'OAuthController',
            function($c) {
                return new \OCA\Grauphel\Controller\OAuthController(
                    $c->query('AppName'),
                    $c->query('Request')
                );
            }
        );
    }
}
?>
