<?php
require_once('ssolib.php');

$tilte = 'Confirm account';

# check whether this was an attempt to confirm
if (empty($_GET)) {
  print_top($title);
  warn('No confirmation code.');
  print_bottom();
  exit;
}

# sanitize the input
$key = addslashes($_GET['key']);

if (empty($key)) {
  print_top($title);
  warn('No confirmation key.');
  print_bottom();
  exit;
}

# get data for key from the registration database
$dh = mysql_connect('localhost', 'registration', 'password');
if (!$dh) {
  print_top($title);
  warn('Cannot connect to MySQL server: ' . mysql_error());
  print_bottom();
  exit;
}

if (!mysql_select_db('registration')) {
  print_top($title);
  warn('Cannot select registration database: ' . mysql_error());
  print_bottom();
  exit;
}

$query = sprintf(
  "SELECT username, password, email, realname
   FROM pending
   WHERE id='%s'",
  mysql_real_escape_string($key)
);

$r = mysql_query($query);
if (!$r) {
  print_top($title);
  warn('Failed to read from registration database: ' . mysql_error());
  print_bottom();
  exit;
}

$row = mysql_fetch_assoc($r);
if (!$row) {
  print_top($title);
  warn('No rows returned from registration database: ' . mysql_error());
  print_bottom();
  exit;
}

extract($row);

# add user to LDAP
$ds = @ldap_connect('localhost');
if (!$ds) {
  print_top($title);
  warn('Cannot connect to LDAP server.');
  print_bottom();
  exit;
}

if (!@ldap_bind($ds, 'uid=worker,dc=test,dc=nomic,dc=net', 'password')) {
  print_top($title);
  warn('Cannot bind to LDAP server: ' . ldap_error($ds));
  print_bottom();
  exit;
}

$attr = array(
  'objectClass'  => 'inetOrgPerson',
  'cn'           => $realname,
  'sn'           => $username,
  'uid'          => $username,
  'mail'         => $email,
  'userPassword' => $password
);

if (!@ldap_add($ds, "uid=$username,ou=people,dc=test,dc=nomic,dc=net", $attr)) {
  print_top($title);
  warn('Cannot write entry: ' . ldap_error($ds));
  print_bottom();
  exit;
} 

@ldap_close($ds);

# remove row from the registration database
$query = sprintf(
  "DELETE FROM pending WHERE id='%s'",
  mysql_real_escape_string($key)
);

$r = mysql_query($query);
if (!$r) {
  print_top($title);
  warn('Failed to remove from registration database: ' . mysql_error());
  print_bottom();
  exit;
}

mysql_close();

# success!
print_top($title);
print '<p>Your account has been activated.</p>';
print_bottom();
exit;

?>
