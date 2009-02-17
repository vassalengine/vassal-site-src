<?php
require_once('AuthDB.php');
require_once('UserDB.php');
require_once('config.php');
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

try {
  # check for blank username
  if (empty($username)) {
    throw new ErrorException('Invalid username.');
  }

  # check for blank password
  if (empty($password)) {
    throw new ErrorException('Blank password.');
  }

  # check for password mismatch
  if ($password != $retype_password) {
    throw new ErrorException('Password mismatch.');
  }

  # check password strength
  if (strlen($password) < 6) {
    throw new ErrorException('Password must be at least 6 characters long.');
  }

  # check for blank email
  if (empty($email)) {
    throw new ErrorException('Blank email.');
  }

  # check for email mismatch
  if ($email != $retype_email) {
    throw new ErrorException('Email mismatch.');
  }

  # check for bad email address
  $validator = new EmailAddressValidator;
  if (!$validator->check_email_address($email)) {
    throw new ErrorException('Bad email address.');
  }

  # check for blank realname
  if (empty($realname)) {
    throw new ErrorException('Blank realname.');
  }

  # check the CAPTCHA
  $resp = recaptcha_check_answer(
    RECAPTCHA_PRIVATE_KEY,
    $_SERVER['REMOTE_ADDR'],
    $_POST['recaptcha_challenge_field'],
    $_POST['recaptcha_response_field']
  );

  if (!$resp->is_valid) {
    throw new ErrorException(
      "The reCAPTCHA wasn't entered correctly. Go back and try it again. " .
      '(reCAPTCHA said: ' . $resp->error . ')');
  }

  # check that the username is not already taken
  $user = new UserDB();
  
  if ($user->exists($username)) {
    throw new ErrorException('The account "' . $username . '" already exists.');
  }

  # build confirmation key
  $key = rand_base64_key();

  # store confirmation information in the database
  $auth = new AuthDB();

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

  $auth->write($query);

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
    throw new ErrorException('Failed to send confirmation email.');
  }

  # success!
  print_top($title);
  print '<p>A confirmation email has been sent.
         Reply to it to activate your account.</p>'; 
  print_bottom();
  exit;
}
catch (ErrorException $e) {
  print_top($title);
  warn($e->getMessage());
  print_form();
  print_bottom();
  exit;
}

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

  echo recaptcha_get_html(RECAPTCHA_PUBLIC_KEY);

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
