<?php

namespace OCA\Grauphel\AppInfo;

use \OCP\AppFramework\App;
use \OCP\AppFramework\Bootstrap\IBootContext;
use \OCP\AppFramework\Bootstrap\IBootstrap;
use \OCP\AppFramework\Bootstrap\IRegistrationContext;
use \OCA\Grauphel\Search\Provider;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'grauphel';

    public function __construct(array $urlParams = array())
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerSearchProvider(Provider::class);
    }

    public function boot(IBootContext $context): void
    {
    }
}
