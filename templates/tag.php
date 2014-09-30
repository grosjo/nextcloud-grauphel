<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>

<div id="app-content" class="content">
  <h1>Notebook: <?php p($_['tag']); ?></h1>
  <ul>
    <?php foreach ($_['notes'] as $note) { ?>
      <li data-id="<?php p($note['guid']); ?>"><a href="#"><?php p($note['title']); ?></a></li>
    <?php } ?>
  </ul>
</div>
