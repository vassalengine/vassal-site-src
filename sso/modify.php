<?php

require_once('AuthDB.php');
require_once('UserDB.php');
require_once('ssolib.php');
require_once('EmailAddressValidator.php');

$title = 'Modify Account';

$key = $_COOKIE['VASSAL_login'];

try {
  if (empty($key)) {
    throw new ErrorException('No key.');
  }

  # check cookie
  $auth = new AuthDB();

  $query = sprintf(
    "SELECT username FROM cookies WHERE id='%s'",
    mysql_real_escape_string($key)
  );

  $row = $auth->read($query);
  if (!$row) {
    throw new ErrorException('No result.');
  }
  
  extract($row);

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
    throw new ErrorException('Password mismatch.');
  }

  # check for email mismatch
  if ($email != $retype_email) {
    throw new ErrorException('Email mismatch.');
  }

  # build the attributes array
  $attr = array();

  if (!empty($password)) {
    # check password strength
    if (strlen($password) < 6) {
      throw new ErrorException('Password must be at least 6 characters long.');
    }

    $attr['userPassword'] = $password;
  }

  if (!empty($realname)) {
    $attr['cn'] = $realname;
  }

  if (empty($attr) && empty($email)) {
    throw new ErrorException('No changes.');
  }

  if (!empty($attr)) {
    # set new attributes in LDAP
    $user = new UserDB();
    $user->modify($username, $attr);
  }

  if (!empty($email)) {
    # check for bad email address
    $validator = new EmailAddressValidator;
    if (!$validator->check_email_address($email)) {
      throw new ErrorException('Bad email address.');
    }

    # build confirmation key
    $key = rand_base64_key();

    # store confirmation information in the database
    $query = sprintf(
      "INSERT INTO confirmemail
       (id, username, email)
       VALUES('%s', '%s', '%s')",
      mysql_real_escape_string($key),
      mysql_real_escape_string($username),
      mysql_real_escape_string($email)
    );

    $auth->write($query);

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
      throw new ErrorException('Failed to send confirmation email.');
    }
  }

  # success!
  print_top($title);
  print '<p>Your settings have been updated.</p>';
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
