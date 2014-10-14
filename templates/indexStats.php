<h2>Statistics</h2>
<dl>
 <dt>Number of <a class="lined" href="<?php p(OCP\Util::linkToRoute('grauphel.gui.tag', array('rawtag' => 'grauphel:special:all'))); ?>">notes</a></dt>
 <dd><?php p($_['notes']); ?></dd>

 <dt>Number of <a class="lined" href="<?php p(OCP\Util::linkToRoute('grauphel.gui.tokens')); ?>">registered apps</a></dt>
 <dd><?php p($_['tokens']); ?></dd>

 <dt>Sync revision</dt>
 <dd><?php p($_['syncrev']); ?></dd>
</dl>
