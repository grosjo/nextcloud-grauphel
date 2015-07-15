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

\OC::$server->getSearch()->registerProvider(
    'OCA\Grauphel\Search\Provider', array('apps' => array('grauphel'))
);

\OCP\Util::addscript('grauphel', 'loader');
?>
