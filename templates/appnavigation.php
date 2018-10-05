<div id="app-navigation">
  <ul>
    <?php foreach ($_['tags'] as $tag) { ?>
      <li data-id="<?php p($tag['id']) ?>" <?php $tag['selected'] && print ' class="selected"'; ?>><a href="<?php p(isset($tag['href']) ? $tag['href'] : '#') ?>"><?php p($tag['name']);?></a></li>
    <?php } ?>
  </ul>

  <div id="app-settings">
    <div id="app-settings-header">
      <button class="settings-button" data-apps-slide-toggle="#app-settings-content"></button>
    </div>
    <div id="app-settings-content" style="display: none;">
      <ul>
        <li><a href="<?php p(OC::$server->getURLGenerator()->linkToRoute('grauphel.gui.index')); ?>">Info and stats</a></li>
      <?php if (OCP\User::isLoggedIn()) { ?>
        <li><a href="<?php p(OC::$server->getURLGenerator()->linkToRoute('grauphel.gui.tokens')); ?>">Manage access tokens</a></li>
        <li><a href="<?php p(OC::$server->getURLGenerator()->linkToRoute('grauphel.gui.database')); ?>">Manage database</a></li>
      <?php } ?>
      </ul>
    </div>
  </div>
</div>
