<?php

# Not a valid entry point, skip unless MEDIAWIKI is defined
if(!defined( 'MEDIAWIKI' )) {
  echo "Auth_SSO extension";
  die();
}

# Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
  'name' => 'VASSAL SSO Authentication -> Auth_SSO',
  'version' => '1.0',
  'author' => 'Joel Uckelman',
  'url' => '',
  'description' => ''
);

require_once('./extensions/ssolib.php');

if (!class_exists('AuthPlugin')) require_once('./includes/AuthPlugin.php');

class Auth_SSO extends AuthPlugin {

  public function userExists($username) {
#    return sso_user_exists($username);
    return true;
  }

  public function authenticate($username, $password) {
    return sso_authenticate($username, $password);
  }

#  public function modifyUITemplate(&$template) {
#  }

#  public function setDomain($domain) {
#    $this->domain = $domain;
#  }
 
#  public function validDomain($domain) {
#    return true; 
#  }

  public function updateUser(&$user) {
    $attr = sso_get_attr($user->getName());            
    $user->setEmail($attr['mail'][0]);
    $user->setRealName($attr['cn'][0]);
    $user->setOption('language', $attr['preferredLanguage'][0]);

    return true;
  }

  public function autoCreate() {
    return true;
  }

  public function allowPasswordChange() {
    return true;
  }
    
  public function setPassword($user, $password) {
    return sso_set_password($user->getName(), $password);
  }

  public function updateExternalDB($user) {
  }

  public function canCreateAccounts() {
    return false;
  }

  public function addUser($user, $password, $email='', $realname='') {
    return false;
  }

  public function strict() {
    return true;
  }

  public function strictUserAuth($username) {
    return true;
  }

  public function initUser(&$user, $autocreate=false) {
    $this->updateUser($user);
    $user->setToken();
    $user->saveSettings();
    return true;
  }

#  public function getCanonicalName($username) {
#    return $username;
#  }

#  public function getUserInstance(User &$user) {
#  }
}


?>
