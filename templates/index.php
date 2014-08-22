<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>

<div id="app-content">
  <div>
    <h1>Tomboy notes server</h1>
    <p>
      Use the following sync server URL with tomboy/conboy/tomdroid:
    </p>
    <pre><?php p($_['apiroot']); ?></pre>
    <p>
      You may also explore the API yourself at
      <a style="text-decoration: underline" href="<?php p($_['apiurl']); ?>">api/1.0</a>.
    </p>
  </div>

  <?php isset($_['stats']) && $_['stats']->printPage(); ?>
</div>
