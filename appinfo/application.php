<?php
namespace OCA\Grauphel\AppInfo;
use \OCP\AppFramework\App;
use \OCA\Grauphel\Lib\Dependencies;

class Application extends App
{
    public function __construct(array $urlParams=array())
    {
        parent::__construct('grauphel', $urlParams);

        $container = $this->getContainer();

        $container->registerService(
            'Session',
            function($c) {
                return $c->query('ServerContainer')->getUserSession();
            }
        );

        /**
         * Controllers
         */
        $container->registerService(
            'ApiController',
            function($c) {
                Dependencies::get()->urlGen
                    = $c->query('ServerContainer')->getURLGenerator();
                return new \OCA\Grauphel\Controller\ApiController(
                    $c->query('AppName'),
                    $c->query('Request'),
                    $c->query('Session')->getUser()
                );
            }
        );
        $container->registerService(
            'OauthController',
            function($c) {
                Dependencies::get()->urlGen
                    = $c->query('ServerContainer')->getURLGenerator();
                return new \OCA\Grauphel\Controller\OauthController(
                    $c->query('AppName'),
                    $c->query('Request'),
                    $c->query('Session')->getUser()
                );
            }
        );
    }
}
?>
