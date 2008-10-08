<?php

/*
 * $Id$
 *
 * Copyright (c) 2008 by Joel Uckelman
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Library General Public
 * License (LGPL) as published by the Free Software Foundation.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Library General Public License for more details.
 *
 * You should have received a copy of the GNU Library General Public
 * License along with this library; if not, copies are available
 * at http://www.opensource.org.
 */

#
# This script receives queries consisting of VASSAL version numbers and
# replies with '1' if we want to receive bug reports from that version
# and '0' otherwise.
#
# At present, we adopt the simplest possible policy and accept reports
# from versions at least as great as some minimal version.
#

# reject unspecified versions
if (!array_key_exists('version', $_GET)) exit; 

# v0 is the least version from which we want reports
$v0 = '3.1.0-beta3';
$v1 = $_GET['version'];

# parse the version numbers
$tok0 = new VassalVersionTokenizer($v0);
$tok1 = new VassalVersionTokenizer($v1);

try {
  while ($tok0->hasNext() && $tok1->hasNext()) {
    $n0 = $tok0->next();
    $n1 = $tok1->next();

    if ($n0 != $n1) reply($v0, $v1, $n1 > $n0 ? 1 : 0);
  }
}
catch (Exception $e) {
  reply($v0, $v1, 0);
}

reply($v0, $v1, !$tok0->hasNext() ? 1 : 0);


function reply($v0, $v1, $result) {
  # log the request
  $time = date("M d H:i:s", $_SERVER['REQUEST_TIME']);
  $fh = fopen('check_version_bug_log', 'ab');
  fwrite($fh, "$time $v0 $v1 $result\n");
  fclose($fh);

  # return the result to the client
  print $result;
  exit;
}

class IllegalArgumentException extends Exception {}
class NoSuchElementException extends Exception {}
class VersionFormatException extends Exception {}

#
# This is taken from VASSAL.tools.version.VassalVersionTokenizer and
# translated into PHP. When VassalVersionTokenizer is changed, this
# must also be updated.
#
class VassalVersionTokenizer {
  private $v;

  const NUM = 0;
  const DELIM = 1;
  const TAG = 2;
  const EOS = 3;
  const END = 4;

  private $state = self::NUM;

  private static $tags = array(
    'beta1' => 3606,
    'beta2' => 3664,
    'beta3' => 4023
  );

  function __construct($version) {
    if ($version == null) throw new IllegalArgumentException();
    $this->v = $version;
  }

  function hasNext() {
    return strlen($this->v) > 0 || $this->state == self::EOS;
  }

  function next() {
    if (!$this->hasNext()) throw new NoSuchElementException();

    while (true) {
      switch ($this->state) {
      case self::NUM:   // read a version number
        if (preg_match('/^\d+/', $this->v, $matches, PREG_OFFSET_CAPTURE) < 1)
          throw new VersionFormatException();
        
        $n = $matches[0][0];
        if (!is_numeric($n)) throw new VersionFormatException();
        if ($n < 0) throw new VersionFormatException();

        $this->v = substr($this->v, $matches[0][1] + strlen($matches[0][0]));
        $this->state = strlen($this->v) == 0 ? self::EOS : self::DELIM;
        return $n;
      case self::DELIM: // eat delimiters
        switch (substr($this->v, 0, 1)) {
        case '.':
          $this->state = self::NUM;
          $this->v = substr($this->v, 1);
          break;
        case '-':
          $this->state = self::TAG;
          $this->v = substr($this->v, 1);
          return -2;
        default:
          throw new VersionFormatException();
        }
        break;
      case self::TAG: // parse the tag 
        if (substr($this->v, 0, 3) == 'svn') {
          // report the svn version
          $this->v = substr($this->v, 3);
          
          $n = $this->v;
          if (!is_numeric($n)) throw new VersionFormatException();
          if ($n < 0) throw new VersionFormatException();
        }
        else if (array_key_exists($this->v, self::$tags)) {
          // convert the tag to an svn version
          $n = self::$tags[$this->v];
        }
        else throw new VersionFormatException();

        $this->v = '';
        $this->state = self::EOS;
        return $n;
      case self::EOS: // mark the end of the string
        $this->state = self::END;
        return -1;
      case self::END: // this case is terminal
        throw new IllegalStateException();
      }
    }      
  }
}

?>
