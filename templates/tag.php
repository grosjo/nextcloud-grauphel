<?php style('grauphel', 'grauphel'); ?>

<?php /** @var $l OC_L10N */ ?>
<?php print_unescaped($_['appNavigation']); ?>

<div id="app-content" class="list">
  <h1>Notebook: <?php p($_['tag']); ?></h1>

  <table class="table" id="grauphel-notes">
   <thead>
    <tr>
     <th id="headerTitle">
      <a href="<?php p($tagUrl); ?>?sortby=title">Title</a>
     </th>
     <th>
      <a href="<?php p($tagUrl); ?>?sortby=date">Modified</a>
     </th>
    </tr>
   </thead>
   <tbody>

    <?php foreach ($_['notes'] as $note) { ?>
     <tr id="note-<?php p($note['guid']); ?>">
      <td>
       <a class="cellclick" href="<?php p($_['urlGen']->linkToRoute('grauphel.gui.note', array('guid' => $note['guid']))); ?>"><?php p(htmlspecialchars_decode($note['title'], ENT_QUOTES | ENT_HTML5)); ?></a>
      </td>
      <td style="color: <?php echo p($note['dateColor']); ?>"
          title="<?php p(date('Y-m-d H:i:s', strtotime($note['last-change-date']))); ?>">
       <?php p($_['date']->formatDate(strtotime($note['last-change-date']))); ?>
      </td>
     </tr>
    <?php } ?>

   </tbody>
  </table>
</div>
