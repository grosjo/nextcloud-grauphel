<style type="text/css">
.app-grauphel #app-content {
    box-sizing: border-box;
    padding: 2ex;
}

.app-grauphel #app-content h1 {
    font-weight: bold;
    font-size: 2em;
    margin-bottom: 1ex;
}
.app-grauphel #app-content h2 {
    font-weight: bold;
    font-size: 150%;
    margin-bottom: 1ex;
    margin-top: 2ex;
}
.app-grauphel #app-content dt {
    font-weight: bold;
}
.app-grauphel #app-content dd {
    margin-left: 3ex;
}
.app-grauphel #app-content pre {
    margin: 1em;
    background-color: #DDD;
    padding: 1ex;
    font-family: monospace;
}
</style>

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
