<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>

<?php /** @var $l OC_L10N */ ?>
<?php $_['appNavigation']->printPage(); ?>

<script type="text/javascript" src="<?php p(OCP\Util::linkTo('grauphel','js/grauphel.js')); ?>"></script>

<div id="app-content" class="list">
  <h1>Manage access tokens</h1>
  <p>
    Here you see which applications have access to the notes.
    You can permanently revoke access by clicking the "delete" icon on the right
    side of each token row.
  </p>
  <table class="table" id="grauphel-tokens">
   <thead>
    <tr>
     <th>Token</th>
     <th>Client</th>
     <th>Last use</th>
    </tr>
   </thead>
   <tbody>
    <?php foreach ($_['tokens'] as $token) { ?>
      <tr id="token-<?php p($token->tokenKey); ?>">
       <td><?php p($token->tokenKey); ?></td>
       <td title="<?php p($token->client); ?>"><?php p($_['client']->getNiceName($token->client)); ?></td>
       <td>
        <?php p(\OCP\Util::formatDate($token->lastuse)); ?>
        <form method="POST" action="<?php p(OCP\Util::linkToRoute('grauphel.token.delete', array('username' => $_['username'], 'tokenKey' => $token->tokenKey))); ?>">
           <input type="hidden" name="delete" value="1" />
           <button type="submit" class="icon-delete delete action"
                   original-title="Delete"
                   data-token="token-<?php p($token->tokenKey); ?>"
           />
        </form>
       </td>
      </tr>
    <?php } ?>
   </tbody>
 </table>
</div>
