<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>

<div id="app-content" class="content">
  <div id="searchresults" class="hidden"></div>
  <div>
    <h1>grauphel - Tomboy notes server</h1>
    <p>
      <a class="lined" href="http://apps.owncloud.com/content/show.php?action=content&amp;content=166654">Grauphel</a>
      is a server to store and synchronize notes from Tomboy and compatible clients.
    </p>
    <p>
      Use the following sync server URL:
    </p>
    <pre><?php p($_['apiroot']); ?></pre>
    <p>
     Supported clients:
    </p>
    <ul>
      <li><a class="lined" href="https://wiki.gnome.org/Apps/Tomboy">Tomboy</a> (Linux, Windows)</li>
      <li><a class="lined" href="http://conboy.garage.maemo.org/">Conboy</a> (Nokia N900 Maemo)</li>
      <li><a class="lined" href="https://launchpad.net/tomdroid">Tomdroid</a> (Android)</li>
    </ul>
    <p>
      You may also explore the API yourself at
      <a class="lined" href="<?php p($_['apiurl']); ?>">api/1.0</a>.
    </p>
  </div>

  <?php isset($_['stats']) && $_['stats']->printPage(); ?>

  <hr style="height: 1px; border: none; background-color: grey"/>
  <p style="text-align: center">
    Written by <a class="lined" href="http://cweiske.de/">Christian Weiske</a>
  </p>
</div>
