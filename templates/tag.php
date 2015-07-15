<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>

<div id="app-content" class="list">
  <div id="searchresults" class="hidden"></div>
  <h1>Notebook: <?php p($_['tag']); ?></h1>

  <table class="table" id="grauphel-notes">
   <thead>
    <tr>
     <th id="headerTitle">Title</th>
     <th>Modified</th>
    </tr>
   </thead>
   <tbody>

    <?php foreach ($_['notes'] as $note) { ?>
     <tr id="note-<?php p($note['guid']); ?>">
      <td>
       <a class="cellclick" href="<?php p(OCP\Util::linkToRoute('grauphel.gui.note', array('guid' => $note['guid']))); ?>"><?php echo ($note['title']); ?></a>
      </td>
      <td style="color: <?php echo p($note['dateColor']); ?>">
       <?php p(\OCP\Util::formatDate(strtotime($note['last-change-date']))); ?>
      </td>
     </tr>
    <?php } ?>

   </tbody>
  </table>
</div>
