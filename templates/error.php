<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<div id="app-content" class="content">
  <div>
    <h1>grauphel - Tomboy notes server</h1>
    <h2>Error</h2>
    <p><?php p($_['message']); ?></p>
    <?php if ($_['code'] == 1001) { ?>
    <p>
     You need to install the PHP PECL OAuth extension to make grauphel work.
     See the
     <a class="lined" href="http://cweiske.de/grauphel.htm#manual">
      installation instructions</a>
     for more information.
    </p>
    <?php } ?>
  </div>
</div>
