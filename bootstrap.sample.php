<?php

$phpunit_test_config = array(
  'user' => 'username',
  'pass' => 'password',
  'cert' => 'certificate.pem',
  'instance' => 'test'

  /* special values used in some test cases */
  /* might be useful to manipulate them for debugging server side apps */
  'dummy_source_object_id'  => 12349,
  'dummy_source_table_name' => 'my_test_table',
);

require 'RIT_Webservices.php';
