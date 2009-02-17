<?php

require_once('ssolib.php');
require_once('recaptchalib.php');
require_once('EmailAddressValidator.php');

$title = 'Create Account';

# check whether this was a registration attempt
if (empty($_POST)) {
  print_top($title);
  print_form();
  print_bottom();
  exit;
}

# sanitize the input
$username = addslashes($_POST['username']);
$password = addslashes($_POST['password']);
$retype_password = addslashes($_POST['retype_password']);
$email = addslashes($_POST['email']);
$retype_email = addslashes($_POST['retype_email']);
$realname = addslashes($_POST['realname']);

# check for blank username
if (empty($username)) {
  print_top($title);
  warn('Invalid username.');
  print_form();
  print_bottom();
  exit;
}

# check for blank password
if (empty($password)) {
  print_top($title);
  warn('Blank password.');
  print_form();
  print_bottom();
  exit;
}

# check for password mismatch
if ($password != $retype_password) {
  print_top($title);
  warn('Password mismatch.');
  print_form();
  print_bottom();
  exit;
}

# check password strength
if (strlen($password) < 6) {
  print_top($title);
  warn('Password must be at least 6 characters long.');
  print_form();
  print_bottom();
  exit;
}

# check for blank email
if (empty($email)) {
  print_top($title);
  warn('Blank email.');
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

# check for bad email address
$validator = new EmailAddressValidator;
if (!$validator->check_email_address($email)) {
  print_top($title);
  warn('Bad email address.');
  print_form();
  print_bottom();
  exit;
}

# check for blank realname
if (empty($realname)) {
  print_top($title);
  warn('Blank realname.');
  print_form();
  print_bottom();
  exit;
}

# check the CAPTCHA
$privatekey = 'privatekey';
$resp = recaptcha_check_answer(
  $privatekey,
  $_SERVER['REMOTE_ADDR'],
  $_POST['recaptcha_challenge_field'],
  $_POST['recaptcha_response_field']
);

if (!$resp->is_valid) {
  print_top($title);
  warn("The reCAPTCHA wasn't entered correctly. Go back and try it again. " .
       '(reCAPTCHA said: ' . $resp->error . ')');
  print_form();
  print_bottom();
  exit;
}

# check that the username is not already taken
$ds = @ldap_connect('localhost');
if (!$ds) {
  print_top($title);
  warn('Cannot connect to LDAP server.'); 
  print_form();
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

$r = @ldap_search($ds, 'ou=people,dc=test,dc=nomic,dc=net', "uid=$username");
if ($r && @ldap_count_entries($ds, $r) > 0) {
  print_top($title);
  warn('The account "' . $username . '" already exists.');
  print_form();
  print_bottom();
  exit;
}

@ldap_close($ds);


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
  "INSERT INTO pending
    (id, username, password, email, realname)
    VALUES('%s', '%s', '%s', '%s', '%s')",
  mysql_real_escape_string($key),
  mysql_real_escape_string($username),
  mysql_real_escape_string($password),
  mysql_real_escape_string($email),
  mysql_real_escape_string($realname)
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

To active this account, simply reply to this message, or open this link in your browser:

http://www.test.nomic.net/confirm.php?key=$key

If you do not wish to activate this account, please disregard this message. If you think your email address is being maliciously associated with this account, or you have any other questions, please send them to webmaster@test.nomic.net.

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

print_top($title);
print '<p>A confirmation email has been sent. Reply to it to activate your account.</p>'; 
print_bottom();
exit;

function print_form() {
  print <<<END
<script>var RecaptchaOptions = { theme : 'white' };</script>
<form class="registration_form" action="register.php" method="post">
  <fieldset>
    <legend>Create an Account</legend>
    <p>Already have an account? <a href="login.php">Log in</a>.</p>
    <table>
      <tr>
        <th><label for="username">Username:</label></th>
        <td><input type="text" id="username" name="username" size="20"/></td>
      </tr>
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
        <td colspan="2">
END;

  $publickey = 'publickey';
  echo recaptcha_get_html($publickey);

print <<<END
        </td>
      </tr>
      <tr>
        <td></td>
        <td><input type="submit" name="create" id="create" value="Create account" /></td>
      </tr>
    </table>
  </fieldset>
</form>
END;
}

?>
