<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>

<div id="app-content" class="list">
  <h1>Manage access tokens</h1>
  <table class="table">
   <thead>
    <tr>
     <th>Token</th>
     <th>Client</th>
     <th>Last use</th>
     <th>Actions</th>
    </tr>
   </thead>
   <tbody>
    <?php foreach ($_['tokens'] as $token) { ?>
      <tr>
       <td><?php p($token->tokenKey); ?></td>
       <td title="<?php p($token->client); ?>"><?php p($_['client']->getNiceName($token->client)); ?></td>
       <td><?php p(\OCP\Util::formatDate($token->lastuse)); ?></td>
       <td>Disable Delete</td>
      </tr>
    <?php } ?>
   </tbody>
 </table>
</div>
