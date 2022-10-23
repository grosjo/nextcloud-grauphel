<?php
namespace OCA\Grauphel\AppInfo;

//$application = new Application();
$application = \OC::$server->query(Application::class);

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
            array(
                'url'  => '/note/{guid}.html',
                'name' => 'notes#html',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/note/{guid}.txt',
                'name' => 'notes#text',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/note/{guid}.xml',
                'name' => 'notes#xml',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/note/{guid}',
                'name' => 'gui#note',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/tokens',
                'name' => 'gui#tokens',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/database',
                'name' => 'gui#database',
                'verb' => 'GET',
            ),
            array(
                'url'  => '/database',
                'name' => 'gui#databaseReset',
                'verb' => 'POST',
            ),

            array(
                'url'  => '/tokens/{username}/{tokenKey}',
                'name' => 'token#delete',
                'verb' => 'DELETE',
            ),
            array(
                'url'  => '/tokens/{username}/{tokenKey}',
                'name' => 'token#deletePost',
                'verb' => 'POST',
            ),
        )
    )
);

?>
