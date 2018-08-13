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

// Performance
DB::q('SET sql_log_bin = 0');

$pattern = arg('pattern', '00');
$test = arg('test', TRUE);

out(($test == TRUE ? 'TEST' : 'PRODUCTION') . " Remove list doublettes on pattern: $pattern");
remove_list_doublettes($pattern, $test);

out ("Done updating");
exit;

//
// Gitte test: 4871cb714f8e1f6901b7d0ac99d1bb93b3356%
//
// Functions:
//
function remove_list_doublettes($owner_pattern="00", $test=TRUE) {

  DB::$db->autocommit(FALSE);

  // Create table of lists which have doublettes
  $result = DB::q('
    CREATE TEMPORARY TABLE doublettes
    SELECT
      MAX(list_id) as list_id,
      COUNT(list_id) as cnt,
      owner,
      type

    FROM lists
    WHERE
      owner LIKE "@pattern"
      AND type <> "user_list"
    GROUP BY owner, type
    HAVING cnt > 1
  ',
    array('@pattern' => $owner_pattern . "%")
  );

  $result = DB::q('
    SELECT count(*) as doublette_types from doublettes
  ');

  output_table($result);


  // Show doublettes

  $result = DB::q('
    SELECT d.list_id, d.type, d.owner, l.list_id as old_id, e.list_id
    FROM doublettes d
    LEFT JOIN lists l ON (d.owner = l.owner AND d.type = l.type)
    LEFT JOIN elements e ON (l.list_id = e.list_id)
  ');
  output_table($result);

  // Update elements. Move to most recent list

  $result = DB::q('
    UPDATE doublettes d
    LEFT JOIN lists l ON (d.owner = l.owner AND d.type = l.type)
    LEFT JOIN elements e ON (l.list_id = e.list_id)
    SET e.list_id = d.list_id
    ');

  out("Updated elements:" . DB::$db->affected_rows);

  // Delete old empty lists

  $result = DB::q('
    DELETE l
    FROM doublettes d
    LEFT JOIN lists l ON (d.owner = l.owner AND d.type = l.type)
    WHERE l.list_id <> d.list_id
  ');

  out("Deleted lists:" . DB::$db->affected_rows);

  // Show doublettes after delete

  $result = DB::q('
    SELECT d.list_id, d.type, d.owner, l.list_id as old_id, e.list_id
    FROM doublettes d
    LEFT JOIN lists l ON (d.owner = l.owner AND d.type = l.type)
    LEFT JOIN elements e ON (l.list_id = e.list_id)
  ');
  output_table($result);

  if ($test) {
    DB::$db->rollback();
    out("Rolling back");
  } else {
    DB::$db->commit();
  }

}
