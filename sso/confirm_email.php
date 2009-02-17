<?php

require_once('ssolib.php');

$tilte = 'Confirm email';

# check whether this was an attempt to confirm
if (empty($_GET)) {
  print_top($title);
  warn('No confirmation key.');
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
  "SELECT username, email
   FROM confirmemail
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

# set new email in LDAP
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

$dn = "uid=$username,ou=people,dc=test,dc=nomic,dc=net";
$attr = array('mail' => $email);
if (!@ldap_mod_replace($ds, $dn, $attr)) {
  print_top($title);
  warn('Cannot set user email: ' . ldap_error($ds));
  print_bottom();
  exit;
}

@ldap_close($ds);

# remove row from the registration database
$query = sprintf(
  "DELETE FROM confirmemail WHERE id='%s'",
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
print '<p>Your email address has been updated.</p>';
print_bottom();
exit;


?>
