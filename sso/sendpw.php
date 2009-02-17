<?php
require_once('AuthDB.php');
require_once('UserDB.php');
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

try {
  # check for blank username and email
  if (empty($username) && empty($email)) {
    throw new ErrorException('You must enter a username or email address.');
  }

  # find the user account in LDAP
  $user = new UserDB();

  # search by username if it was given, otherwise by email
  $entries = $auth->search(empty($username) ? "mail=$email" : "uid=$username");
  $count = $entries['count'];
  if ($count < 1) {
    throw new ErrorException(
      !empty($username) ?
        'The account "' . $username . '" does not exist.' :
        'There is no account for the address "' . $email . '".'
    );
  }

  # store password change keys in the database
  $auth = new AuthDB();

  for ($i = 0; $i < $count; ++$i) {
    $e = $entries[$i]; 

    $key = rand_base64_key();

    $query = sprintf(
      "INSERT INTO resetpw (id, username) VALUES('%s', '%s')",
      mysql_real_escape_string($key),
      mysql_real_escape_string($e['uid'][0])
    );

    $auth->write($query);

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
      throw new ErrorException('Failed to send confirmation email.');
    }
  }

  # success!
  print_top($title);
  print '<p>A password reset email has been sent, which contains a link you can follow to reset your password.</p>';
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
