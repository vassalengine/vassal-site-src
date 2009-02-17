<?php
require_once('AuthDB.php');
require_once('UserDB.php');
require_once('ssolib.php');

$tilte = 'Confirm account';

try {
  # check whether this was an attempt to confirm
  if (empty($_GET)) {
    throw new ErrorException('No confirmation code.');
  }

  # sanitize the input
  $key = addslashes($_GET['key']);

  if (empty($key)) {
    throw new ErrorException('No confirmation key.');
  }

  # get data for key from the registration database
  $auth = new AuthDB();

  $query = sprintf(
    "SELECT username, password, email, realname
     FROM pending
     WHERE id='%s'",
    mysql_real_escape_string($key)
  );

  $row = $auth->read($query);
  if (!$row) {
    throw new ErrorException('No results.');
  }

  extract($row);

  # add user to LDAP
  $user = new UserDB();

  $attr = array(
    'objectClass'  => 'inetOrgPerson',
    'cn'           => $realname,
    'sn'           => $username,
    'uid'          => $username,
    'mail'         => $email,
    'userPassword' => $password
  );

  $user->create($username, $attr);

  # remove row from the registration database
  $query = sprintf(
    "DELETE FROM pending WHERE id='%s'",
    mysql_real_escape_string($key)
  );
  $auth->write($query);

  # success!
  print_top($title);
  print '<p>Your account has been activated.</p>';
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
