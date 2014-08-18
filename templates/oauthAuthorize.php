<form method="post" action="<?php p($_['formaction']); ?>">
 <input type="hidden" value="<?php p($_['requesttoken']); ?>" name="requesttoken" />
 <input type="hidden" value="<?php p($_['oauth_token']); ?>" name="oauth_token" />
 <p>
  Shall application FIXME get full access to the notes?
 </p>
 <button type="submit" name="auth" value="ok">Yes, authorize</button>
 <button type="submit" name="auth" value="cancel">No, decline</button>
</form>
