<?php

$summary = 'Yet Another Test bug report';
$email = 'uckelman@nomic.net';
$description = 'This is a test bug report, a test.';

$url = 'http://sourceforge.net/tracker/index.php';

$param = array(
  'group_id'          => '90612',
  'atid'              => '594231',
  'func'              => 'postadd',
  'category_id'       => '100',
  'artifact_group_id' => '100',
  'assigned_to'       => '100',
  'priority'          => '5',
  'summary'           => "ABR: $summary",
  'details'           => "$email\n\n$description",
  'file_description'  => 'the errorLog',
  'input_file'        => '@/home/uckelman/projects/VASSAL/site-src/util/xx00',
  'submit'            => 'Add Artifact'
);


$headers = array(
  'Expect:'  // lighttpd bug
);

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_USERAGENT, 'curl/7.19.4 (x86_64-redhat-linux-gnu) libcurl/7.19.4 NSS/3.12.3 zlib/1.2.3 libidn/1.9 libssh2/1.0');

curl_exec($ch);

echo curl_getinfo($ch, CURLINFO_HEADER_OUT);


curl_close($ch);
?>
