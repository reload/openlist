<?php

/**
 * @file
 * Test preferences module.
 */

define('ALWAYS_PRINT_RESULT', FALSE);

include_once 'header.php';

$owner = 'TEST_USER';

line('Test Preferences', TRUE);
line();

curlit('Preferences/get', array('owner' => $owner, 'key' => 'cons'));
curlit('Preferences/set', array(), array(
  'owner' => $owner,
  'key' => 'cons',
  'value' => FALSE,
));
curlit('Preferences/get', array('owner' => $owner, 'key' => 'cons'));

curlit('Preferences/set', array(
  'owner' => $owner,
  'key' => 'cons',
  'value' => TRUE,
));
curlit('Preferences/get', array('owner' => $owner, 'key' => 'cons'));

include_once 'footer.php';
