<?php

/**
 * @file
 * Admin tool.
 */

error_reporting(E_ALL);

require_once dirname(__FILE__) . '/../../settings.php';
require_once OPENLIST_CLASSES_PATH . '/Dev.php';
require_once OPENLIST_CLASSES_PATH . '/DB.php';
require_once dirname(__FILE__) . '/helpers.php';

// Performance.
DB::q('SET sql_log_bin = 0');

$pattern = arg('pattern', '0');
$test = arg('test', TRUE);

out(($test == TRUE ? 'TEST' : 'PRODUCTION') . " Remove user list doublettes on pattern: $pattern");
remove_list_doublettes($pattern, $test);

out("Done updating");
exit;

/**
 * Remove function.
 */
function remove_list_doublettes($owner_pattern = "00", $test = TRUE) {

  DB::$db->autocommit(FALSE);

  // Create table of lists which have doublettes.
  $result = DB::q('
    CREATE TEMPORARY TABLE doublettes
    SELECT
    	library_code, title, owner, COUNT(list_id) cnt, MAX(list_id) latest_list_id
    FROM
    	lists
    WHERE
      owner LIKE "@pattern"
    	AND type = "user_list"
      AND status = 1
    GROUP BY
    	library_code, title, owner
    HAVING
    	cnt > 1
  ',
    array('@pattern' => $owner_pattern . "%")
  );

  // Show doublettes.
  $result = DB::q('
    SELECT d.latest_list_id, d.owner, l.list_id old_id, COUNT(e.list_id) element_count
    FROM doublettes d
    LEFT JOIN lists l ON (d.owner = l.owner AND l.type = "user_list" AND l.title = d.title AND l.list_id != d.latest_list_id)
    LEFT JOIN elements e ON (l.list_id = e.list_id)
    GROUP BY d.latest_list_id, d.owner, l.list_id
    ORDER BY d.latest_list_id, d.owner, l.list_id
  ');
  output_table($result);

  // Update elements. Move to most recent list.
  $result = DB::q('
    UPDATE doublettes d
    LEFT JOIN lists l ON (d.owner = l.owner AND l.type = "user_list" AND l.title = d.title AND l.list_id != d.latest_list_id)
    LEFT JOIN elements e ON (l.list_id = e.list_id)
    SET e.list_id = d.latest_list_id
  ');

  out("Updated elements:" . DB::$db->affected_rows);

  // Delete old empty lists.
  $result = DB::q('
    DELETE l
    FROM doublettes d
    LEFT JOIN lists l ON (d.owner = l.owner AND l.type = "user_list" AND l.title = d.title AND l.list_id != d.latest_list_id)
    WHERE l.list_id <> d.latest_list_id
  ');

  out("Deleted lists:" . DB::$db->affected_rows);

  // Show doublettes after delete.
  $result = DB::q('
    SELECT d.latest_list_id, d.owner, l.list_id old_id, COUNT(e.list_id) element_count
    FROM doublettes d
    LEFT JOIN lists l ON (d.owner = l.owner AND l.type = "user_list" AND l.title = d.title AND l.list_id != d.latest_list_id)
    LEFT JOIN elements e ON (d.latest_list_id = e.list_id)
    GROUP BY d.latest_list_id, d.owner, l.list_id
    ORDER BY d.latest_list_id, d.owner, l.list_id
  ');
  output_table($result);

  if ($test) {
    DB::$db->rollback();
  }
  else {
    DB::$db->commit();
  }

}
