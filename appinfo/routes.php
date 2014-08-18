<?php
namespace OCA\Grauphel\AppInfo;

$application = new Application();
$application->registerRoutes(
    $this,
    array(
        'routes' => array(
            array(
                'url'  => '/test',
                'name' => 'access#test',
            ),

            array(
                'url'  => '/authorize',
                'name' => 'access#authorize',
                'verb' => 'POST',
                ),
            array(
                'url'  => '/login',
                'name' => 'access#login',
                'verb' => 'GET',
            ),

            array(
                'url'  => '/oauth/access_token',
                'name' => 'oauth#accessToken',
                'verb' => 'POST',
            ),
            array(
                'url'  => '/oauth/authorize',
                'name' => 'oauth#authorize',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/oauth/confirm',
                'name' => 'oauth#confirm',
                'verb' => 'POST',
            ),
            array(
                'url'  => '/oauth/request_token',
                'name' => 'oauth#requestToken',
                'verb' => 'POST',
            ),

            array(
                'url'  => '/api/1.0',
                'name' => 'api#index',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/api/1.0/{user}/note/{guid}',
                'name' => 'api#note',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/api/1.0/{user}/notes',
                'name' => 'api#notes',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/api/1.0/{user}/notes',
                'name' => 'api#notes',
                'verb' => 'POST',
            ),
            array(
                'url'  => '/api/1.0/{user}',
                'name' => 'api#user',
                'verb' => 'GET',
            ),
        )
    )
);

?>
