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

// Performance
DB::q('SET sql_log_bin = 0');

$pattern = arg('pattern', '00');
$test = arg('test', TRUE);

out(($test == TRUE ? 'TEST' : 'PRODUCTION') . " Remove element doublettes on pattern: $pattern");

remove_element_doublettes($pattern, $test);

$time_elapsed_secs = microtime(true) - $start;
out ("Done updating:" . $time_elapsed_secs);
exit;

//
// Functions:
//
function remove_element_doublettes($owner_pattern="00", $test=TRUE) {

  DB::$db->autocommit(FALSE);

  // Create table of elements which have doublettes within each list
  $result = DB::q('
    CREATE TEMPORARY TABLE originals
    SELECT
      MAX(e.element_id) as element_id,
      COUNT(e.element_id) as cnt,
      l.list_id,
      l.owner,
      l.type,
      e.`data`
    FROM lists l
    LEFT JOIN elements e ON (e.list_id = l.list_id)
    WHERE
      l.owner LIKE "@pattern"
    GROUP BY l.owner, l.list_id, l.type, e.data
  ',
    array('@pattern' => $owner_pattern . "%")
  );

  // create index idx_data on originals(list_id, `data`(25));
  $result = DB::q('
   CREATE INDEX idx_e on originals(element_id);
  ');

  $result = DB::q('
    SHOW INDEX FROM originals;
  ');
 
  output_table($result, TRUE, 50);

  $result = DB::q('
    SHOW INDEX FROM elements;
  ');
  output_table($result, TRUE, 50);


  $result = DB::q('
    SELECT *,LENGTH(data)*cnt l from originals ORDER BY cnt DESC
  ');

  output_table($result, TRUE, 50);

  $result = DB::q('
    SELECT list_id, count(*) elem from originals GROUP BY list_id ORDER BY elem DESC
  ');

  output_table($result, TRUE, 50);
  

  $result = DB::q('
    SELECT count(*) as original_elements, SUM(cnt) as dubsum from originals
  ');

  output_table($result);

  // Show doublettes
//  FORCE INDEX FOR JOIN (list_id)

  $result = DB::q('
    EXPLAIN 
    SELECT count(*)
    FROM lists l
    LEFT JOIN elements e ON (e.list_id = l.list_id)
    LEFT JOIN originals o ON (e.element_id = o.element_id)
    WHERE
      l.owner LIKE "@pattern" AND
      o.element_id IS NULL 
  ');
  output_table($result);

  $result = DB::q('
    DELETE e
    FROM lists l
    LEFT JOIN elements e ON (e.list_id = l.list_id)
    LEFT JOIN originals o ON (e.element_id = o.element_id)
    WHERE
      l.owner LIKE "@pattern" AND
      o.element_id IS NULL
  ',
    array('@pattern' => $owner_pattern . "%")
  );
  
  // Show doublettes after delete
  if ($test) {
    $result = DB::q('
      SELECT
        MAX(e.element_id) as element_id,
        COUNT(e.element_id) as cnt,
        l.list_id,
        l.owner,
        l.type,
        e.`data`
      FROM lists l
      LEFT JOIN elements e ON (e.list_id = l.list_id)
      WHERE
        l.owner LIKE "@pattern"
      GROUP BY l.owner, l.list_id, l.type, e.data
      HAVING cnt >= 1
    ',
      array('@pattern' => $owner_pattern . "%")
    );

    output_table($result);
  }
  
  if ($test) {
    DB::$db->rollback();
  } else {
    DB::$db->commit();
  }

}
