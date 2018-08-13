<?php

/**
 * @file
 * Admin tool.
 */

error_reporting(E_ALL);
header('Content-type: text/html; charset=utf-8');

require_once dirname(__FILE__) . '/../../settings.php';
require_once OPENLIST_CLASSES_PATH . '/Dev.php';
require_once OPENLIST_CLASSES_PATH . '/DB.php';
require_once dirname(__FILE__) . '/helpers.php';

// Performance.
DB::q('SET sql_log_bin = 0');

$pattern = arg('pattern', '');
$pattern = arg('test', TRUE);

out(($test === TRUE ? 'TEST' : 'PRODUCTION') . " Remove user list duoblettes on pattern: $pattern");
remove_list_doublettes(710100, $pattern, $test);

out("Done updating");
exit;

/**
 * Remove function.
 */
function remove_list_doublettes($library_code, $owner_pattern = "00", $test = TRUE) {

  DB::$db->autocommit(FALSE);

  $changed = array();
  foreach (explode("\n", file_get_contents(__DIR__ . '/changed/' . $library_code)) as $line) {
    if (strpos($line, ' -> ') !== FALSE) {
      $parts = explode(' -> ', $line);
      $changed[$parts[0]] = $parts[1];
    }
  }

  $cnt = 0;
  foreach ($changed as $old => $new) {
    out($old . ' -> ' . $new);

    $result = DB::q('
      SELECT
        *
      FROM
        elements
      WHERE
        library_code = @library_code
        AND data LIKE "@search"
    ', array(
      '@library_code' => $library_code,
      '@search' => '%:' . $old . '"%',
    ));

    output_table($result, TRUE, FALSE);

    // Reset data pointer.
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
      $data = unserialize($row['data']);
      $parts = explode(':', $data['value']);

      if ($parts[1] == $old) {
        $parts[1] = $new;
        $data['value'] = implode(':', $parts);
        out($data);
      }
    }

    if ($cnt++ > 2) {
      break;
    }
  }

  if ($test) {
    DB::$db->rollback();
  }
  else {
    DB::$db->commit();
  }

}
