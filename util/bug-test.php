<?php

$summary = 'Test bug report';
$email = 'uckelman@nomic.net';
$description = 'This is a test bug report.';

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
  'submit'            => 'Add Artifact'
);

$boundary = "---------------------------" . base_convert(mt_rand(), 10, 36) .
  base_convert(mt_rand(), 10, 36) . base_convert(mt_rand(), 10, 36);

$data = '';

// set up each parameter
foreach ($param as $key => $value) {
  $data .= "--$boundary\r\n";
  $data .= "Content-Disposition: form-data; name=\"$key\"\r\n";
  $data .= "\r\n$value\r\n";
}

$data .= "--$boundary\r\n";

$headers = array(
#  'User-Agent: Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.1) Gecko/20090630 Fedora/3.5-1.fc11 Firefox/3.5',
#  'Referer: https://sourceforge.net/tracker/?func=add&group_id=90612&atid=594231',
  "Content-Type: multipart/form-data; boundary=$boundary",
);

$opts = array(
  'http' => array(
    'method'     => 'POST',
    'header'     => implode("\r\n", $headers),
    'content'    => $data,
    'user-agent' => 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.1) Gecko/20090630 Fedora/3.5-1.fc11 Firefox/3.5',
  )
);

$ctx = stream_context_create($opts);
$content = file_get_contents($url, 0, $ctx);

echo $content;

?>
