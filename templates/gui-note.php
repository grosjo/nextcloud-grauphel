<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>

<script type="text/javascript" src="<?php p(OCP\Util::linkTo('grauphel','js/grauphel.js')); ?>"></script>

<div id="app-content" class="content">
 <h1><?php p($_['note']->title); ?></h1>
 <p class="muted">
  Last modified:
  <?php p(\OCP\Util::formatDate(strtotime($_['note']->{'last-change-date'}))); ?>
 </p>
 <div class="note-content">
  <?php echo $_['note-content']; ?>
 </div>
</div>
