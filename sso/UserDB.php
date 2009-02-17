<?php

require_once('config.php');

class UserDB {
  private $ds;  

  public function __construct() {
    $this->ds = @ldap_connect(LDAP_HOST);
    if (!$this->ds) {
      throw new ErrorException('Cannot connect to LDAP server.');
    }
  }

  public function __destruct() {
    @ldap_close($this->ds);
  }

  public function search($filter) {
    if (!@ldap_bind($this->ds)) {
      throw new ErrorException(
        'Cannot bind to LDAP server: ' . ldap_error($this->ds)); 
    }

    $r = @ldap_search($this->ds, LDAP_BASE, $filter);
    if (!$r) {
      throw new ErrorException(
        'LDAP search failed: ' . ldap_error($this->ds));
    }

    return @ldap_get_entries($this->ds, $r);
  }

  public function exists($username) {
    return ${$this->search("uid=$username")}['count'] > 0;
  }

  public function auth($username, $password) {
    $this->bind();

    $r = @ldap_search($this->ds, LDAP_BASE, "uid=$username");
    if (!$r) {
      throw new ErrorException(
        'LDAP search failed: ' . ldap_error($this->ds));
    }

    if (@ldap_count_entries($this->ds, $r) < 1) {
      throw new ErrorException(
        'The account "' . $username . '" does not exist.');
    }

    $result = @ldap_get_entries($this->ds, $r);

    if (!@ldap_bind($this->ds, $result[0]['dn'], $password)) {
      throw new ErrorException('Bad password.');
    }
  }

  public function modify($username, $attr) {
    $this->bind(LDAP_USERNAME, LDAP_PASSWORD);
  
    $dn = "uid=$username," . LDAP_BASE;
    if (!@ldap_mod_replace($this->ds, $dn, $attr)) {
      throw new ErrorException(
        'Cannot reset user attributes: ' . ldap_error($this->ds));
    }
  }

  public function create($username, $attr) {
    $this->bind(LDAP_USERNAME, LDAP_PASSWORD);
   
    $dn = "uid=$username," . LDAP_BASE;
    if (!@ldap_add($this->ds, $dn, $attr)) {
      throw new ErrorException(
        'Cannot write entry: ' . ldap_error($ds));
    }
  }

  private function bind($dn = NULL, $pw = NULL) {
    if (!@ldap_bind($this->ds, $dn, $pw)) {
      throw new ErrorException(
        'Cannot bind to LDAP server: ' . ldap_error($this->ds));
    }
  } 
}

?>
