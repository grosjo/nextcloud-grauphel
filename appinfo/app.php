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
?>
