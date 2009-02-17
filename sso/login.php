<?php
require_once('AuthDB.php');
require_once('UserDB.php');
require_once('ssolib.php');

$title = 'VASSAL Login';

# check whether this was a login attempt
if (empty($_POST)) {
  print_top($title);
  print_form();
  print_bottom();
  exit;
}

# sanitize the input
$username = addslashes($_POST['username']);
$password = addslashes($_POST['password']);

try {
  # check for blank username
  if (empty($username)) {
    throw new ErrorException('Invalid username.');
  }

  # check for blank password
  if (empty($password)) {
    throw new ErrorException('Blank password.');
  }

  # authenticate with LDAP server
  $user = new UserDB();
  $user->auth($username, $password);

  # MediaWiki login
  $url = 'http://www.test.nomic.net/wiki/api.php';
  $data = array(
    'format'     => 'php',
    'action'     => 'login',
    'lgname'     => $username,
    'lgpassword' => $password,
    'lgdomain'   => 'test'
  );

  $cookies = array();

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'read_header');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $result = curl_exec($ch);
  curl_close($ch);

  $result = unserialize($result);
  $result = $result['login'];

  if ($result['result'] != 'Success') {
    if ($result['result'] == 'Illegal') {
      throw new ErrorException('MediaWiki login failed: Invalid username.');
    }
    else if ($result['result'] == 'NotExists') {
      throw new ErrorException('MediaWiki login failed: Invalid username.');
    }
    else if ($result['result'] == 'WrongPass') {
      throw new ErrorException('MediaWiki login falied: Invalid password.');
    }
    else if ($result['result'] == 'WrongPluginPass') {
      throw new ErrorException('MediaWiki login failed: Invalid password.');
    }
    else {
      throw new ErrorException('MediaWiki login failed: ' . $result['result']);
    }
  }

  # set MediaWiki cookies
  foreach ($cookies as $name => $attr) {
    setrawcookie(
      $name,
      $attr['value'],
      array_key_exists('expires', $attr) ? strtotime($attr['expires']) : 0,
      $attr['path'],
      'www.test.nomic.net',
      false,
      array_key_exists('httponly', $attr)
    );
  }

  # phpBB login 
  define('IN_PHPBB', true);
  $phpbb_root_path = 'forum/';
  $phpEx = 'php';
  include($phpbb_root_path . 'common.' . $phpEx);

  $auth = new auth();
  $user = new user();

  $user->session_begin();
  $auth->acl($user->data);
  $user->setup();

  $autologin = true;
  $viewonline = true;

  if (!$user->data['is_registered']) {
    $result = $auth->login($username, $password, $autologin, $viewonline);

    if ($result['status'] != LOGIN_SUCCESS) {
      throw new ErrorException('phpBB login failed.');
    }
  }

  # Bugzilla login
  $cookies = array();

  $params = array(
    'login'    => $username,
    'password' => $password
  #  'remember' => true
  );

  $request = xmlrpc_encode_request('User.login', $params);
  $header = array(
    'Content-type: text/xml',
    'Content-length: ' . strlen($request)
  );

  $url = 'http://www.test.nomic.net/bugzilla/xmlrpc.cgi';

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'read_header');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
  $data = curl_exec($ch);
  curl_close($ch);

  foreach ($cookies as $name => $attr) {
    setrawcookie(
      $name,
      $attr['value'],
      array_key_exists('expires', $attr) ? strtotime($attr['expires']) : 0,
      $attr['path'],
      'www.test.nomic.net',
      false,
      array_key_exists('httponly', $attr)
    );
  }

  # FIXME: loop in case we have a cookie collision

  # set our login cookie
  $key = rand_base64_key();
  $expires = time() + (60 * 60 * 24 * 30);

  $auth = new AuthDB();

  $query = sprintf(
    "INSERT INTO cookies (id, username, expires)
     VALUES('%s', '%s', FROM_UNIXTIME(%s))",
    mysql_real_escape_string($key),
    mysql_real_escape_string($username),
    mysql_real_escape_string($expires)
  );

  $auth->write($query);

  setrawcookie(
    'VASSAL_login',
    $key,
    $expires,
    '/',
    'www.test.nomic.net',
    false,
    true
  );
    
  #header('Location: http://www.test.nomic.net/wiki/index.php/Main_Page');
  #header('Location: http://www.test.nomic.net/forum');

  print_top($title);
  print '<a href="http://www.test.nomic.net/wiki">wiki</a><br/>';
  print '<a href="http://www.test.nomic.net/forum">forum</a><br/>';
  print '<a href="http://www.test.nomic.net/bugzilla">bugs</a><br/>';
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

function read_header($ch, $string) {
  $length = strlen($string);

  if (!strncmp($string, "Set-Cookie:", 11)) {
    parse_cookie_header($string);
  }

  return $length;
}

function parse_cookie_header($header) {
  global $cookies;

  $cookiestr = trim(substr($header, 11, -1));
  $crumbs = explode(';', $cookiestr);
    
  $tmp = explode('=', array_shift($crumbs));
  $name = trim($tmp[0]);
  $cookies[$name]['value'] = trim($tmp[1]);
  
  foreach ($crumbs as $crumb) {
    $tmp = explode('=', $crumb);
    $cookies[$name][strtolower(trim($tmp[0]))] =
      sizeof($tmp) > 1 ? trim($tmp[1]) : null;
  }
}

function print_form() {
  print <<<END
<form class="loginform" action="login.php" method="post">
  <fieldset>
    <legend>Login</legend>
    <p>Don't have an account? <a href="register.php">Create an account</a>.</p>
    <table>
      <tr>
        <th><label for="username">Username:</label></th>
        <td><input type="text" id="username" name="username" size="20"/></td>
      </tr>
      <tr>
        <th><label for="password">Password:</label></th>
        <td><input type="password" id="password" name="password" size="20"/></td>
      </tr>
      <tr>
        <td></td>
        <td><a href="sendpw.php">I forgot my password!</a></td>
      </tr>
      <tr>
        <td></td>
        <td><input type="submit" name="login" id="login" value="Log in" /></td>
      </tr>
    </table>
  </fieldset>
</form>
END;
}

?>
