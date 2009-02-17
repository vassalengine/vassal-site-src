<?php

require_once('ssolib.php');
require_once('EmailAddressValidator.php');

$title = 'Modify Account';

$key = $_COOKIE['VASSAL_login'];
if (empty($key)) {
  print_top($title);
  warn('No key.');
  print_form();
  print_bottom();
  exit;
}

# check cookie
$dh = mysql_connect('localhost', 'registration', 'password');
if (!$dh) {
  print_top($title);
  warn('Cannot connect to MySQL server: ' . mysql_error());
  print_form();
  print_bottom();
  exit;
}

if (!mysql_select_db('registration')) {
  print_top($title);
  warn('Cannot select registration database: ' . mysql_error());
  print_form();
  print_bottom();
  exit;
}

$query = sprintf(
  "SELECT username FROM cookies WHERE id='%s'",
  mysql_real_escape_string($key)
);

$r = mysql_query($query);
if (!$r) {
  print_top($title);
  warn('Failed to read from registration database: ' . mysql_error());
  print_form();
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

mysql_close();

# check whether this was a modification attempt
if (empty($_POST)) {
  print_top($title);
  print_form();
  print_bottom();
  exit;
}

# sanitize the input
$password = addslashes($_POST['password']);
$retype_password = addslashes($_POST['retype_password']);
$email = addslashes($_POST['email']);
$retype_email = addslashes($_POST['retype_email']);
$realname = addslashes($_POST['realname']);

# check for password mismatch
if ($password != $retype_password) {
  print_top($title);
  warn('Password mismatch.');
  print_form();
  print_bottom();
  exit;
}

# check for email mismatch
if ($email != $retype_email) {
  print_top($title);
  warn('Email mismatch.');
  print_form();
  print_bottom();
  exit;
}

# build the attributes array
$attr = array();

if (!empty($password)) {
  # check password strength
  if (strlen($password) < 6) {
    print_top($title);
    warn('Password must be at least 6 characters long.');
    print_form();
    print_bottom();
    exit;
  }

  $attr['userPassword'] = $password;
}

if (!empty($realname)) {
  $attr['cn'] = $realname;
}

if (empty($attr) && empty($email)) {
  print_top($title);
  warn('No changes.');
  print_form();
  print_bottom();
  exit;
}

if (!empty($attr)) {
  # set new attributes in LDAP
  $ds = @ldap_connect('localhost');
  if (!$ds) {
    print_top($title);
    warn('Cannot connect to LDAP server.');
    print_form();
    print_bottom();
    exit;
  }
  
  if (!@ldap_bind($ds, 'uid=worker,dc=test,dc=nomic,dc=net', 'password')) {
    print_top($title);
    warn('Cannot bind to LDAP server: ' . ldap_error($ds));
    print_form();
    print_bottom();
    exit;
  }
  
  $dn = "uid=$username,ou=people,dc=test,dc=nomic,dc=net";
  if (!@ldap_mod_replace($ds, $dn, $attr)) {
    print_top($title);
    warn('Cannot reset user password: ' . ldap_error($ds));
    print_form();
    print_bottom();
    exit;
  }
  
  @ldap_close($ds);
}

if (!empty($email)) {
  # check for bad email address
  $validator = new EmailAddressValidator;
  if (!$validator->check_email_address($email)) {
    print_top($title);
    warn('Bad email address.');
    print_form();
    print_bottom();
    exit;
  }

  # build confirmation key
  $key = rand_base64_key();

  # store confirmation information in the database
  $dh = mysql_connect('localhost', 'registration', 'password');
  if (!$dh) {
    print_top($title);
    warn('Cannot connect to MySQL server: ' . mysql_error());
    print_form();
    print_bottom();
    exit;
  }

  if (!mysql_select_db('registration')) {
    print_top($title);
    warn('Cannot select registration database: ' . mysql_error());
    print_form();
    print_bottom();
    exit;
  }

  $query = sprintf(
    "INSERT INTO confirmemail
     (id, username, email)
     VALUES('%s', '%s', '%s')",
    mysql_real_escape_string($key),
    mysql_real_escape_string($username),
    mysql_real_escape_string($email)
  );

  $r = mysql_query($query);
  if (!$r) {
    print_top($title);
    warn('Failed to write to registration database: ' . mysql_error());
    print_form();
    print_bottom();
    exit;
  }

  mysql_close();

  # send confirmation email
  $subject = 'vassalengine.org email address confirmation';
  $message = <<<END
Someone claiming to be "$realname", probably you, from IP address {$_SERVER['REMOTE_ADDR']}, has attempted to register the account "$username" with this email address at vassalengine.org.

To confirm this email address, simply reply to this message, or open this link in your browser:

http://www.test.nomic.net/confirm_email.php?key=$key

If you do not wish to switch to this email address, please disregard this message. If you are not requesting to change the email address associated with this account, or you have any other questions, please contact webmaster@test.nomic.net.

END;

  $message = wordwrap($message, 70);
  $headers =
    "From: webmaster@test.nomic.net\r\n" .
    "Reply-To: confirm+$key@test.nomic.net\r\n";

  if (!mail($email, $subject, $message, $headers)) {
    print_top($title);
    warn('Failed to send confirmation email.');
    print_form();
    print_bottom();
    exit;
  }
}

# success!
print_top($title);
print '<p>Your settings have been updated.</p>';
print_bottom();
exit;


function print_form() {
  print <<<END
<form class="modify_form" action="modify.php" method="post">
  <fieldset>
    <legend>Modify Settings</legend>
    <table>
      <tr>
        <th><label for="password">Password:</label></th>
        <td><input type="password" id="password" name="password" size="20"/></td
>
      </tr>
      <tr>
        <th><label for="retype_password">Retype password:</label></th>
        <td><input type="password" id="retype_password" name="retype_password" size="20"/></td
>
      </tr>
      <tr>
        <th><label for="email">Email address:</label></th>
        <td><input type="text" id="email" name="email" size="20"/></td
>
      </tr>
      <tr>
        <th><label for="retype_email">Retype email address:</label></th>
        <td><input type="text" id="retype_email" name="retype_email" size="20"/></td>
      </tr>
      <tr>
        <th><label for="realname">Real name:</label></th>
        <td><input type="text" id="realname" name="realname" size="20"/></td>
      </tr>
      <tr>
        <td></td>
        <td><input type="submit" name="modify" id="modify" value="Modify account" /></td>
      </tr>
    </table>
  </fieldset>
</form>
END;
}

?>
