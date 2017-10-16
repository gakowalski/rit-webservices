<?php

$phpunit_test_config = array(
  'user' => 'username',
  'pass' => 'password',
  'cert' => 'certificate.pem',
  'instance' => 'test',

  /* special values used in some test cases */
  /* might be useful to manipulate them for debugging server side apps */
  'dummy_source_object_ids'  => array(12350, 12351, 12352), //< 3 element array
  'dummy_source_table_name' => 'my_test_table',
  'wait_for_completed_transaction' => 5, //< number of seconds passed to sleep()
);

require 'RIT_Webservices.php';
