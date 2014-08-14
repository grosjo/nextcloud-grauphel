<?php

// Check if we are a user
OCP\User::checkLoggedIn();

$somesetting = OCP\Config::getSystemValue( "somesetting", '' );
OCP\App::setActiveNavigationEntry( 'grauphel' );
$tmpl = new OCP\Template( 'grauphel', 'main', 'user' );
$tmpl->assign( 'somesetting', $somesetting );
$tmpl->printPage();
