<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>

<script type="text/javascript" src="<?php p(OCP\Util::linkTo('grauphel','js/grauphel.js')); ?>"></script>

<div id="app-content" class="content">
  <div id="searchresults" class="hidden"></div>
  <h1>Manage database</h1>
  <p>
    In case something went seriously wrong during synchronization, you may reset your
    database.
    It will delete all your notes and the synchronization status - as if you
    never synced to this server before.
  </p>

  <?php isset($_['stats']) && $_['stats']->printPage(); ?>

  <h2>Reset database</h2>
  <?php if ($_['reset'] === true) { ?>
  <p class="success">
   Database has been reset!
  </p>
  <?php } else if ($_['reset'] === false) { ?>
  <p class="error">
   Database has <b>not</b> been reset!
  </p>
  <?php } ?>

  <p>
   To reset the database, enter your user name and click "reset database":
  </p>
  <form method="POST" action="<?php p(OC::$server->getURLGenerator()->linkToRoute('grauphel.gui.databaseReset')); ?>">
    <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>"/>
    <p>
     <label>Username: <input type="text" name="username" value="" autocomplete="off" /></label>
     <button type="submit">Reset database</button>
    </p>
  </form>
</div>
