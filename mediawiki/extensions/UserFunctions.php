<?php
/*
 
 Defines a subset of parser functions that trigger on the current user.
 
 {{#ifanon:then|else}}    Tests whether the current user is anonymous.
 
 {{#ifblocked:then|else}} Tests whether the current user is blocked.
 
 {{#ifsysop:then|else}}   Tests whether the current user is a sysop.
 
 {{#username:alt}}        Returns the current username.  If the user is not
                          logged in, this function returns the given alternate
                          text, or the user IP if no alternate is provided.

 {{#useremail:alt}}       Returns the current user's e-mail address. If the
                          user is not logged in, this function returns the
                          given alternate text, or the user IP if no alternate
                          is provided.
 
 {{#nickname:alt}}        Returns the current user's nickname. If the user has
                          no nickname, returns the username. If the user is not
                          logged in, this function returns the given alternate
                          text, or the user IP if no alternate is provided.

 {{#ifingroup:group|then|else}}  Tests whether the current user is a member
                                 of the group "group".

 These functions can be exploited to superficially hide information from
 sysops.  Use them with caution.
 
 Author: Algorithm [http://meta.wikimedia.org/wiki/User:Algorithm] et al.
 Version 1.3 (February 13, 2010) added useremail
 Version 1.2 (July 25, 2008)
 
*/

$wgExtensionFunctions[] = 'wfUserFunctions';
$wgExtensionCredits['parserhook'][] = array(
  'name' => 'UserFunctions',
  'version' => '1.3',
  'url' => 'http://www.mediawiki.org/wiki/Extension:UserFunctions',
  'author' => 'Algorithm et al.',   
  'description' => 'Provides a set of dynamic parser functions that trigger on the current user.'
);

$wgHooks['LanguageGetMagic'][] = 'wfUserFunctionsLanguageGetMagic';

function wfUserFunctions() {
  global $wgParser, $wgExtUserFunctions;
         $magicWords['nickname']  = array( 0, 'nickname' );
  $wgExtUserFunctions = new ExtUserFunctions();
 
  $wgParser->setFunctionHook( 'ifanon', array( &$wgExtUserFunctions, 'ifanon' ) );
  $wgParser->setFunctionHook( 'ifblocked', array( &$wgExtUserFunctions, 'ifblocked' ) );
  $wgParser->setFunctionHook( 'ifsysop', array( &$wgExtUserFunctions, 'ifsysop' ) );
  $wgParser->setFunctionHook( 'username', array( &$wgExtUserFunctions, 'username' ) );
  $wgParser->setFunctionHook( 'useremail', array( &$wgExtUserFunctions, 'useremail' ) );
  $wgParser->setFunctionHook( 'nickname', array( &$wgExtUserFunctions, 'nickname' ) );
  $wgParser->setFunctionHook( 'ifingroup', array( &$wgExtUserFunctions, 'ifingroup' ) );
}
 
function wfUserFunctionsLanguageGetMagic( &$magicWords, $langCode ) {
  switch ( $langCode ) {
  default:
    $magicWords['ifanon']    = array( 0, 'ifanon' );
    $magicWords['ifblocked'] = array( 0, 'ifblocked' );
    $magicWords['ifsysop']   = array( 0, 'ifsysop' );
    $magicWords['username']  = array( 0, 'username' );
    $magicWords['useremail']  = array( 0, 'useremail' );
    $magicWords['nickname']  = array( 0, 'nickname' );
    $magicWords['ifingroup']  = array( 0, 'ifingroup' );
  }
  return true;
}

class ExtUserFunctions {
  function ifanon( &$parser, $then = '', $else = '' ) {
    global $wgUser;
    $parser->disableCache();
 
    if($wgUser->isAnon())
    {
      return $then;
    }
      return $else;
  }
 
  function ifblocked( &$parser, $then = '', $else = '' ) {
    global $wgUser;
    $parser->disableCache();
 
    if($wgUser->isBlocked()) {
      return $then;
    }
    return $else;
  }
 
  function ifsysop( &$parser, $then = '', $else = '' ) {
    global $wgUser;
    $parser->disableCache();
 
    if($wgUser->isAllowed('protect')) {
      return $then;
    }
    return $else;
  }
 
  function username( &$parser, $alt = '' ) {
    global $wgUser;
    $parser->disableCache();
 
    if($wgUser->isAnon() && $alt!=='') {
      return $alt;
    }
    return $wgUser->getName();
  }

  function useremail( &$parser, $alt = '' ) {
    global $wgUser;
    $parser->disableCache();
 
    if($wgUser->isAnon() && $alt!=='') {
      return $alt;
    }
    return $wgUser->getEmail();
  }

  function nickname( &$parser, $alt = '' ) {
    global $wgUser;
    $parser->disableCache();

    if($wgUser->isAnon()) {
      if ( $alt!=='') {
        return $alt;
      }
      return $wgUser->getName();
    }
    $nickname = $wgUser->getOption( 'nickname' );
    $nickname = $nickname === '' ? $wgUser->getName() : $nickname;
    return $nickname;
  }

  function ifingroup( &$parser, $grp = '', $then = '', $else = '' ) {
    global $wgUser;
    $parser->disableCache();
    if($grp!==''){
      if(in_array($grp,$wgUser->getEffectiveGroups())) {
        return $then;
      }
      return $else;
    }
    else return $else;
  }
}
