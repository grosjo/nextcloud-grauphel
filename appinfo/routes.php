<?php
namespace OCA\Grauphel\AppInfo;

$application = new Application();
$application->registerRoutes(
    $this,
    array(
        'routes' => array(
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
                'url'  => '/api/1.0/',
                'name' => 'api#indexSlash',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/api/1.0/{username}',
                'name' => 'api#user',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/api/1.0/{username}/notes',
                'name' => 'api#notes',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/api/1.0/{username}/notes',
                'name' => 'api#notesSave',
                'verb' => 'PUT',
            ),
            array(
                'url'  => '/api/1.0/{username}/note/{guid}',
                'name' => 'api#note',
                'verb' => 'GET',
            ),

            array(
                'url'  => '/',
                'name' => 'gui#index',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/tag/{rawtag}',
                'name' => 'gui#tag',
                'verb' => 'GET',
            ),
        )
    )
);

?>
