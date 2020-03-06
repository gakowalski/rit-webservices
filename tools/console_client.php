<?php

require '../RIT_Webservices.php';

$options = getopt('', [
  'channel:',
  'password:',
  'certificate:',
  'test',
  'get:',
  'send:',
  'language:',
]);

if (isset($options['channel']) === false) {
  system('cat console_client.txt');
  exit(-1);
}

$channel = $options['channel'];
$pass = $options['password'] ?? null;
$cert = $options['certificate'] ?? null;
$env = isset($options['test']) ? 'test' : 'production';
$lang = $options['language'] ?? 'pl-PL';

$webservice = new RIT_Webservices($channel, $pass, $cert, $env, true);

if (isset($options['get'])) {
  $target = $options['get'];

  if (is_numeric($target)) {
    $result = $webservice->get_object_by_id($target, $lang);
  } else {
    switch ($target) {
      case 'all':
        $result = $webservice->get_all_objects($lang);
        break;

      case 'metadata':
        $result = $webservice->get_metadata($lang);
        break;

      default:
        echo "Unsupported GET target '$target' \n";
    }
  }
} else if (isset($options['send'])) {
  $filename = $options['send'];
  if (file_exists($filename)) {
    $json = file_get_contents($filename);
    if ($json === false) {
      echo "Can't read from $filename\n";
    } else {
      $obj = json_decode($json);
      $result = $webservice->add_object($obj);
    }
  } else {
    echo "File '$filename' doesn't exist\n";
  }
}

if (isset($result)) {
  var_dump($webservice->xml_request);
  var_dump($webservice->xml_response);
  var_dump($result);
}
