<?php

/*
 * $Id: bug.php 4183 2008-10-02 18:39:18Z uckelman $
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
# This script exists to collect bug reports from VASSAL.tools.BugDialog
# and redirect them on to our bug tracker of choice.
#

#
# Read bug report
#
if (!array_key_exists('version', $_POST)) die('Not a bug report.');

$time = date("M d H:i:s", $_SERVER['REQUEST_TIME']);
$version = $_POST['version'];
$email = $_POST['email'];
$summary = $_POST['summary'];
$description = $_POST['description'];
$log = $_FILES['log']['tmp_name']);

#
# Log bug report in case something goes wrong
#
$fh = fopen('bug_log', 'ab');
fwrite($fh, "$time\n$email\n$summary\n\n$description\n\n$log\n\n\n");
fclose($fh);

#
# Relay bug report on to bug tracker at SourceForge 
#
$url = 'http://sourceforge.net/tracker/index.php';

$param = array(
  'group_id'          => '90612',
  'atid'              => '594231',
  'func'              => 'postadd',
  'category_id'       => '100',
  'artifact_group_id' => '100',
  'summary'           => "ABR: $summary",
  'details'           => "$email\n\n$description",
  'file_description'  => 'the errorLog',
  'input_file'        => "@$log",
  'submit'            => 'Add Artifact'
);

$headers = array(
  'Expect:'  // lighttpd bug
);

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
#curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_USERAGENT, 'curl/7.19.4 (x86_64-redhat-linux-gnu) libcurl/7.19.4 NSS/3.12.3 zlib/1.2.3 libidn/1.9 libssh2/1.0');

$result = curl_exec($ch);
return curl_errno($ch); 

?>
