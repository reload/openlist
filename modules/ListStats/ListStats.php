<?php

class ListStats extends Module
{
  public $version = 1;

  /**
   * Abstract getEvents().
   */
  public function getEvents() {
    return array();
  }

  /**
   *
   */
  public function lists() {
    $result = DB::q('
SELECT l.type, l.library_code, COUNT(DISTINCT l.list_id) lcount, COUNT(e.element_id) ecount
FROM lists l
JOIN elements e ON (e.list_id = l.list_id)
WHERE l.library_code = "@library_code"
GROUP BY l.type
    ', array(
      '@library_code' => $GLOBALS['library_code'],
    ));

    if ($result) {
      $lists = array();
      while ($row = $result->fetch_assoc()) {
        $lists[$row['type']] = array(
          'lists' => $row['lcount'],
          'elements' => $row['ecount'],
        );
      }
      return $lists;
    }

    return array();
  }
}

new ListStats();
