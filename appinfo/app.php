<?php
\OC::$server->getNavigationManager()->add(
    array( 
        'id' => 'grauphel',
        'order' => 2342,
        'href' => \OCP\Util::linkToRoute('grauphel.gui.index'),
        'icon' => \OCP\Util::imagePath('grauphel', 'app.svg'),
        'name' => 'Tomboy notes'
    )
);
\OC_Search::registerProvider('OCA\Grauphel\Search\Provider');
\OCP\Util::addscript('grauphel', 'loader');
?>
