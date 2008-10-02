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
# This script exists to collect bug reports from VASSAL.tools.BugDialog
# and redirect them on to our bug tracker of choice.
#

#
# Read bug report
#
$time = date("M d H:i:s", $_SERVER['REQUEST_TIME']);
$email = $_POST['email'];
$summary = $_POST['summary'];
$description = $_POST['description'];
$log = file_get_contents($_FILES['log']['tmp_name']);

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
  'summary'           => "Test: $summary",
  'details'           => "$email\n\n$description",
  'file_description'  => 'the errorLog',
  'submit'            => 'SUBMIT'
);

$boundary = "---------------------------" . base_convert(mt_rand(), 10, 36) .
  base_convert(mt_rand(), 10, 36) . base_convert(mt_rand(), 10, 36);

$data = '';

// set up each parameter
foreach ($param as $key => $value) {
  $data .= "--$boundary\r\n";
  $data .= "Content-Disposition: form-data; name=\"$key\"\r\n";
  $data .= "\r\n$value\r\n";
  $data .= "--$boundary\r\n";
}

// attach the log
$data .= "--$boundary\r\n";
$data .= "Content-Disposition: form-data; name=\"input_file\"; filename=\"errorLog\"\r\n";
$data .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
$data .= "\r\n" . $log . "\r\n";
$data .= "--$boundary--\r\n";

// build the request
$ctx = stream_context_create(array(
  'http' => array(
    'method'  => 'POST',
    'header'  => "Content-Type: multipart/form-data; boundary=$boundary",
    'content' => $data
  )
));

// post the request
$fp = fopen($url, 'rb', false, $ctx);
echo $fp ? 0 : 1;   // return 1 on error
// don't bother to read the result, it's too complex to parse easily
fclose($fp);
?>
