<?php

/**
 * @file
 * Admin tool.
 */

error_reporting(E_ALL);
$start = microtime(true);

require_once dirname(__FILE__) . '/../../settings.php';
require_once OPENLIST_CLASSES_PATH . '/Dev.php';
require_once OPENLIST_CLASSES_PATH . '/DB.php';
require_once dirname(__FILE__) . '/helpers.php';

// Performance.
DB::q('SET sql_log_bin = 0');

$pattern = arg('pattern', '');
$test = TRUE;

out(($test === TRUE ? 'TEST' : 'PRODUCTION') . " Remove user list duoblettes on pattern: $pattern");
remove_list_doublettes($pattern, $test);

$time_elapsed_secs = microtime(true) - $start;
out ("Done updating:" . $time_elapsed_secs);
exit;

/**
 * Remove function.
 */
function remove_list_doublettes($owner_pattern = "00", $test = TRUE) {

  DB::$db->autocommit(FALSE);

  // Create table of huge lists
  $result = DB::q('
    CREATE TEMPORARY TABLE huge
    SELECT
    	l.library_code, l.created, from_unixtime(l.modified), l.list_id, l.title, l.type, COUNT(e.list_id) cnt
    FROM
    	lists l
      JOIN elements e ON (e.list_id = l.list_id)
    WHERE
      l.owner LIKE "@pattern"
    GROUP BY
    	list_id
    HAVING
    	cnt > 500
    ORDER BY
      cnt DESC
  ',
    array('@pattern' => $owner_pattern . "%")
  );

  // Show count
  $result = DB::q('
    SELECT COUNT(*) list_count, SUM(cnt) elements_count FROM huge
  ');

  output_table($result);

  // Show huge lists
  $result = DB::q('
    SELECT * FROM huge
  ');
  output_table($result);

  if ($test) {
    DB::$db->rollback();
  }
  else {
    DB::$db->commit();
  }

}
