<?php script('grauphel', 'grauphel'); ?>

<div id="app-navigation">
  <!-- Tags list -->
   <div class="app-navigation-header">Tags</div>
  <ul class="app-navigation-list">
    <?php foreach ($_['tags'] as $tag) { ?>
      <li class="app-navigation-entry <?php $tag['selected'] && print 'selected' ?>"
          data-id="<?php p($tag['id']) ?>">
        <a href="<?php p($tag['href']) ?>"><?php p($tag['name']); ?></a>
      </li>
    <?php } ?>
  </ul>

  <!-- Statistics -->
  <div class="app-navigation-info">
    <ul>
      <li>Notes: <?php p($_['notes_count']); ?></li>
      <li>Tags: <?php p($_['tags_count']); ?></li>
  </ul>
  </div>

  <!-- Settings button -->
  <div id="app-settings">
    <div id="app-settings-header">
      <button class="settings-button" data-apps-slide-toggle="#app-settings-content">
        Grauphel settings
      </button>
    </div>
    <div id="app-settings-content">
      <ul>
        <li><a href="<?php p($_['urlGen']->linkToRoute('grauphel.gui.index')); ?>">Info and stats</a></li>
        <?php if ($_['isLoggedIn']) { ?>
          <li><a href="<?php p($_['urlGen']->linkToRoute('grauphel.gui.tokens')); ?>">Manage access tokens</a></li>
          <li><a href="<?php p($_['urlGen']->linkToRoute('grauphel.gui.database')); ?>">Manage database</a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
</div>
