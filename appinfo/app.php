<?php
//OCP\App::registerAdmin( 'apptemplate', 'settings' );

OCP\App::addNavigationEntry(
    array( 
        'id' => 'grauphel',
        'order' => 2342,
        'href' => \OCP\Util::linkToRoute('grauphel.gui.index'),
        'icon' => OCP\Util::imagePath('grauphel', 'notes.png'),
        'name' => 'Tomboy notes'
    )
);
\OC_Search::registerProvider('OCA\Grauphel\Search\Provider');
\OCP\Util::addscript('grauphel', 'loader');
?>
