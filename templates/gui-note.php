<?php style('grauphel', 'grauphel'); ?>

<?php /** @var $l OC_L10N */ ?>
<?php print_unescaped($_['appNavigation']); ?>

<div id="app-content" class="content">
 <div class="actions">
  <a class="button" href="<?php echo p($_['links']['html']); ?>">HTML</a>
  <a class="button" href="<?php echo p($_['links']['text']); ?>">Text</a>
  <a class="button" href="<?php echo p($_['links']['json']); ?>">JSON</a>
  <a class="button" href="<?php echo p($_['links']['xml']); ?>">XML</a>
 </div>
 <h1><?php p(htmlspecialchars_decode($_['note']->title, ENT_QUOTES | ENT_HTML5)); ?></h1>
 <p class="muted">
  Last modified:
  <?php p($_['date']->formatDate(strtotime($_['note']->{'last-change-date'}))); ?>
 </p>
 <div class="note-content">
  <?php echo $_['note-content']; ?>
 </div>
</div>
