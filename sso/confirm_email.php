<?php

require_once('AuthDB.php');
require_once('UserDB.php');
require_once('ssolib.php');

$tilte = 'Confirm email';

try {
  # check whether this was an attempt to confirm
  if (empty($_GET)) {
    throw new ErrorException('No confirmation key.');
  }

  # sanitize the input
  $key = addslashes($_GET['key']);

  if (empty($key)) {
    throw new ErrorException('No confirmation key.');
  }

  # get data for key from the registration database
  $auth = new AuthDB();
  
  $query = sprintf(
    "SELECT username, email
     FROM confirmemail
     WHERE id='%s'",
    mysql_real_escape_string($key)
  );

  $row = $auth->read($query);
  if (!$row) {
    throw new ErrorException('No results');
  }

  extract($row);

  # set new email in LDAP
  $user = new UserDB();
  $user->modify($username, array('mail' => $email));
  
  # remove row from the registration database
  $query = sprintf(
    "DELETE FROM confirmemail WHERE id='%s'",
    mysql_real_escape_string($key)
  );

  $auth->write($query);

  # success!
  print_top($title);
  print '<p>Your email address has been updated.</p>';
  print_bottom();
  exit;
}
catch (ErrorException $e) {
  print_top($title);
  warn($e->getMessage());
  print_bottom();
  exit;
}

?>
