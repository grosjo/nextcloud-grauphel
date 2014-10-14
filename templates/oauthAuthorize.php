<link rel="stylesheet" href="<?php p(OCP\Util::linkTo('grauphel','grauphel.css')); ?>" type="text/css"/>
<form method="post" action="<?php p($_['formaction']); ?>" class="oauth-authorize">
 <input type="hidden" value="<?php p($_['requesttoken']); ?>" name="requesttoken" />
 <input type="hidden" value="<?php p($_['oauth_token']); ?>" name="oauth_token" />
 <p class="msg">
  Shall application
  <strong><?php p($_['clientTitle']); ?></strong>
  get full access to the notes?
 </p>
 <p>
  <label>
   Application/computer name:
   <input type="text" name="client" value="<?php p($_['clientAgent']); ?>"/>
  </label>
  (e.g. <tt>home pc</tt>)
 </p>
 <div class="buttons">
  <button type="submit" name="auth" value="ok">Yes, authorize</button>
  <button type="submit" name="auth" value="cancel">No, decline</button>
 </div>
</form>
