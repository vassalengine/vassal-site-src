<?php

require_once('ssolib.php');

$title = 'Reset Password';

# check whether this was a request attempt
if (empty($_POST)) {
  print_top($title);
  print_form();
  print_bottom();
  exit;
}

# sanitize the input
$username = addslashes($_POST['username']);
$email = addslashes($_POST['email']);

# check for blank username and email
if (empty($username) && empty($email)) {
  print_top($title);
  warn('You must enter a username or email address.');
  print_form();
  print_bottom();
  exit;
}

# find the user account in LDAP
$ds = @ldap_connect('localhost');
if (!$ds) {
  print_top($title);
  warn('Cannot connect to LDAP server.');
  print_bottom();
  exit;
}

if (!@ldap_bind($ds)) {
  print_top($title);
  warn('Cannot bind to LDAP server: ' . ldap_error($ds));
  print_form();
  print_bottom();
  exit;
}

# search by username if it was given
$filter = empty($username) ? "mail=$email" : "uid=$username";
$result = null; 

$r = @ldap_search($ds, 'ou=people,dc=test,dc=nomic,dc=net', $filter);
if (!$r) {
  print_top($title);
  warn('LDAP search failed: ' . ldap_error($ds));
  print_form();
  print_bottom();
  exit;
}

$count = @ldap_count_entries($ds, $r);
if ($count < 1) {
  print_top($title);

  if (!empty($username)) {
    warn('The account "' . $username . '" does not exist.');
  }
  else {
    warn('There is no account for the address "' . $email . '".');
  }

  print_form();
  print_bottom();
  exit;
}

$entries = @ldap_get_entries($ds, $r);

# store password change keys in the database
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

for ($i = 0; $i < $count; ++$i) {
  $e = $entries[$i]; 

  $key = rand_base64_key();

  $query = sprintf(
    "INSERT INTO resetpw (id, username) VALUES('%s', '%s')",
    mysql_real_escape_string($key),
    mysql_real_escape_string($e['uid'][0])
  );

  $r = mysql_query($query);
  if (!$r) {
    print_top($title);
    warn('Failed to write to password reset database: ' . mysql_error());
    print_form();
    print_bottom();
    exit;
  }

  # send confirmation email
  $subject = 'vassalengine.org password reset';
  $message = <<<END
Someone. probably you, from IP address {$_SERVER['REMOTE_ADDR']}, has requested that a new password be set for your account at vassalengine.org.

To reset the password for your account, simply open this link in your browser:

http://www.test.nomic.net/resetpw.php?key=$key

If you do not wish to reset your password, please disregard this message. If you receive multiple such notifications which you did not request, or you have any other questions, please conact webmaster@test.nomic.net.

END;

  $message = wordwrap($message, 70);
  $headers = 'From: webmaster@test.nomic.net';

  if (!mail($e['mail'][0], $subject, $message, $headers)) {
    print_top($title);
    warn('Failed to send confirmation email.');
    print_form();
    print_bottom();
    exit;
  }
}

mysql_close();
@ldap_close($ds);

# success!
print_top($title);
print '<p>A password reset email has been sent, which contains a link you can follow to reset your password.</p>';
print_bottom();
exit;

function print_form() {
  print <<<END
<form class="sendpwform" action="sendpw.php" method="post">
  <fieldset>
    <legend>Send password</legend>
    <table>
      <tr>
        <th><label for="username">Username:</label></th>
        <td><input type="text" id="username" name="username" size="20"/></td>
      </tr>
      <tr>
        <th colspan="2">OR</th>
      </tr>
      <tr>
        <th><label for="email">Email address:</label></th>
        <td><input type="text" id="email" name="email" size="20"/></td>
      </tr>
      <tr>
        <td></td>
        <td><input type="submit" name="sendpw" id="sendpw" value="Send password" /></td>
      </tr>
    </table>
  </fieldset>
</form>
END;
}

?>
