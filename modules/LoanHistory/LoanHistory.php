<?php

/**
 * @file
 * Ting Object Ratings module.
 */

class LoanHistory extends Module {
  public $version = 1;

  /**
   * The table.
   */
  private $table = 'm_loan_history';

  /**
   * Abstract getEvents().
   */
  public function getEvents() {
    return array(
      'createElement' => 'onElementCreated',
      'deleteElement' => 'onDeleteElement',
    );
  }
  
  /**
   * Get suggestions, depending on a given object.
   */
  public function getSuggestion($object_id, $owner = FALSE, $limit = 12) {
    if ($owner !== FALSE) {
      $owner_where = '
  AND t2.owner != "@owner"';
    }

    $result = DB::q('
SELECT t2.object_id, COUNT(t2.object_id) AS counts
FROM !table t1 JOIN !table t2 ON (t2.owner = t1.owner)
WHERE
  t1.object_id = "@object_id"
  AND t2.object_id != t1.object_id' . $owner_where . '
GROUP BY
  t2.object_id
ORDER BY
  counts DESC
LIMIT
  0, ' . $limit,
    array(
      '!table' => $this->table,
      '@object_id' => $object_id,
      '@owner' => $owner,
    ));

    $buffer = array();
    while ($row = $result->fetch_assoc()) {
      $buffer[] = $row;
    }

    return $buffer;
  }
  
  /**
   * Reconstruct the tables
   */
  public function reconstruct($from = 0) {
    $from_created = date('Y-m-d', $from);
    
    $result = DB::q('
SELECT
  DISTINCT l.owner, e.created, e.library_code, e.data
FROM
  lists l
  JOIN elements e ON (e.list_id = l.list_id)
WHERE
  l.type = "user_loan_history"
  AND l.status = 1
  AND e.status = 1
  AND e.created > "@from"
ORDER BY
  e.created
    ', array(
      '@from' => $from_created
    ));
    
    DB::q('TRUNCATE TABLE !table', array('!table' => $this->table));
    
    
    $sql = 'INSERT IGNORE INTO !table (owner, object_id, created, library_code) VALUES ';
    
    $i = 0;
    while ($row = $result->fetch_assoc()) {
      $i += 1;
      $data = unserialize($row['data']);
      
      $id = isset($data['value']) ? $data['value'] : $data['id'];
      $created = strtotime($row['created']);
      
      $sql .= '("' . $row['owner'] . '", "' . $id . '", "' . date('Ym', $created) . '", "' . $row['library_code'] . '"), ';
      
      if ($i % 10000 == 0) {
        $sql = substr($sql, 0, -2);
        DB::q($sql, array('!table' => $this->table));
        
        $hist = DB::getHistory();
        unset($hist['sql'], $hist['sqlString']);
        
        $sql = 'INSERT IGNORE INTO !table (owner, object_id, created, library_code) VALUES ';
      }
    }
    
    if (strlen($sql) > 80) {
      $sql = substr($sql, 0, -2);
      DB::q($sql, array('!table' => $this->table));
      
      $hist = DB::getHistory();
      unset($hist['sql'], $hist['sqlString']);
    }
    
    return TRUE;
  }

  /**
   * On element deleted.
   */
  protected function onDeleteElement($element_id) {
    return TRUE;
  }

  /**
   * On element created.
   */
  protected function onElementCreated($element_id, $list_id, $data) {
    return TRUE;
  }

  /**
   * Create the module table on install.
   */
  protected function _install() {
    DB::q('
CREATE TABLE IF NOT EXISTS !table (
  owner varchar(128) NOT NULL,
  object_id char(32) NOT NULL,
  created VARCHAR(6) NOT NULL,
  library_code varchar(128) NOT NULL,
  PRIMARY KEY (owner, object_id)
) ENGINE = InnoDB
    ', array('!table' => $this->table));
    
    return TRUE;
  }

  /**
   * Remove the module table on uninstall.
   */
  protected function _uninstall() {
    DB::q('DROP TABLE IF EXISTS !table', array('!table' => $this->table));

    return TRUE;
  }
}

new LoanHistory();
