<?php

function validate_cookie($username, $key) {
  $ret = false;

  $dh = mysql_connect('localhost', 'registration', 'password');
  if ($dh) {
    if (mysql_select_db('registration')) {
      $query = sprintf(
         "SELECT username FROM pending WHERE id='%s'",
         mysql_real_escape_string($key)
      );

      $r = mysql_query($query);
      if ($r) {
        $row = mysql_fetch_assoc($r);
        if ($row) {
          if ($row['username'] == $username) {
            $ret = true;
          }
        }
      }
    }

    mysql_close();
  }

  return $ret;
}

function sso_user_exists($username) {
  $dn = 'ou=people,dc=test,dc=nomic,dc=net';
  $ret = false;

  $ds = @ldap_connect('localhost');
  if ($ds) {
    if (@ldap_bind($ds)) {
      $r = @ldap_search($ds, $dn, "uid=$username");
      if ($r) {
        if (@ldap_count_entries($ds, $r) > 0) {
          $ret = true;
        }
      }
    }
    @ldap_close($ds);
  }

  return $ret; 
}

function sso_authenticate($username, $password) {
  $dn = 'ou=people,dc=test,dc=nomic,dc=net';
  $ret = false;

  $ds = @ldap_connect('localhost');
  if ($ds) {
    if (@ldap_bind($ds)) {
      $r = @ldap_search($ds, $dn, "uid=$username");
      if ($r) {
        if (@ldap_count_entries($ds, $r) > 0) {
          $result = @ldap_get_entries($ds, $r);
          if (@ldap_bind($ds, $result[0]['dn'], $password)) {
            $ret = true;
          }
        }
      }
    }
    @ldap_close($ds);
  }

  return $ret; 
}

function sso_set_password($username, $password) {
  $ret = false;

  $ds = @ldap_connect('localhost');
  if ($ds) {
    if (@ldap_bind($ds, 'uid=worker,dc=test,dc=nomic,dc=net', 'password')) {
      $dn = "uid=$username,ou=people,dc=test,dc=nomic,dc=net";
      $attr = array('userPassword' => $password);
      if (@ldap_mod_replace($ds, $dn, $attr)) {
        $ret = true;
      }
    }
    @ldap_close($ds);
  }

  return $ret;
}

function sso_get_attr($username) {
  $dn = 'ou=people,dc=test,dc=nomic,dc=net';
  $ret = false;

  $ds = @ldap_connect('localhost');
  if ($ds) {
    if (@ldap_bind($ds)) {
      $r = @ldap_search($ds, $dn, "uid=$username");
      if ($r) {
        if (@ldap_count_entries($ds, $r) > 0) {
          $ret = @ldap_get_entries($ds, $r);
          $ret = $ret[0];
        }
      }
    }
    @ldap_close($ds);
  }

  return $ret; 
}



function rand_base64_key() {
  $key = base64_encode(pack('L6', mt_rand(), mt_rand(), mt_rand(),
                                  mt_rand(), mt_rand(), mt_rand()));
  return strtr($key, '+/=', '-_');
}

function warn($err) {
  print '<div class="errorbox"><h2>Error:</h2>' . $err . '</div>';
}

function print_top($title) {
  print <<<END
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head profile="http://www.w3.org/2005/10/profile">
  <link rel="stylesheet" type="text/css" href="style.css"/>
  <link rel="icon" type="image/png" href="VASSAL.png"/>
  <title>$title</title>
</head>
<body>
END;
}

function print_bottom() {
  print <<<END
</body>
</html>
END;
}

?>
