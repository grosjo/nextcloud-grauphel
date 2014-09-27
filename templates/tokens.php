<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>

<div id="app-content">
  <h1>Manage access tokens</h1>
  <ul>
    <?php foreach ($_['tokens'] as $token) { ?>
      <li data-id="<?php p($token->tokenKey); ?>"><a href="#"><?php p($token->tokenKey); ?></a></li>
    <?php } ?>
  </ul>
</div>
