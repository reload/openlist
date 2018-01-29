<?php
/**
 * Ting Object Ratings module
 */

/**
 * OpenList Module TingObjectRating
 *
 * Handle ratings of materials
 * @see OpenList::callModule()
 *
 */
class TingQuery extends Module {
  /**
   * Module version.
   * @ignore
   */
  public $version = 1;

  /**
   * The table.
   */
  private $table = 'm_tingquery';

  /**
   * Abstract getEvents().
   * @ignore
   */
  public function getEvents() {
    return array(
      'createElement' => 'onElementCreated',
      'editElement' => 'onEditElement',
      'deleteElement' => 'onDeleteElement',
    );
  }

  /**
   * Get public lists the specific ting_id is added to.
   *
   * @param string $ting_id
   *   The ting object id.
   *
   * @return array
   *   List IDs where the ting object is present.
   */
  public function getPublicLists($ting_id) {
    $result = DB::q('
SELECT
  DISTINCT e.list_id
FROM
  elements e
  JOIN !table tq ON (e.element_id = tq.element_id)
  JOIN lists l ON (l.list_id = e.list_id)
WHERE
  tq.ting_id = "@ting_id"
  AND l.library_code IN (?$library_access)
ORDER BY
  e.list_id
    ', array(
      '!table' => $this->table,
      '@ting_id' => $ting_id,
      '?$library_access' => $GLOBALS['library_access'],
    ));

    $buffer = array();

    while ($row = $result->fetch_assoc()) {
      $buffer[] = $row['list_id'];
    }

    return $buffer;
  }

  /**
   * Rebuild the TingQuery table.
   *
   * @param string $admin
   *   The admin password
   *   .
   * @return bool
   *   If the rebuild was done.
   */
  public function rebuild($admin) {
    if ($admin !== OPENLIST_ADMIN_GET_PASSWORD) {
      return FALSE;
    }

    $sql = DB::parseSql('
SELECT e.element_id, e.data
FROM elements e
LEFT JOIN !table tq ON (tq.element_id = e.element_id)
JOIN lists l ON (l.list_id = e.list_id)
WHERE
	e.status = 1
  AND l.status = 1
  AND tq.element_id IS NULL
    ', array(
      '!table' => $this->table,
    ));

    $result = DB::$db->query($sql);

    while ($row = $result->fetch_assoc()) {
      $data = unserialize($row['data']);
      if ($data['type'] == 'ting_object') {
        DB::q('
INSERT INTO !table
(element_id, ting_id)
VALUES (%element_id, "@ting_id")
  ON DUPLICATE KEY UPDATE
    ting_id = "@ting_id"
        ', array(
          '!table' => $this->table,
          '%element_id' => $row['element_id'],
          '@ting_id' => $data['value'],
        ));
      }
    }

    return TRUE;
  }

  /**
   * On element deleted.
   * @ignore
   */
  protected function onDeleteElement($element_id) {
    DB::q('
DELETE FROM !table
WHERE
  element_id = %element_id
    ', array(
      '!table' => $this->table,
      '%element_id' => $element_id,
    ));

    return TRUE;
  }

  /**
   * On element edited.
   * @ignore
   */
  protected function onEditElement($element_id, $data) {
    if ($data['type'] == 'ting_object') {
      DB::q('
INSERT INTO !table
(element_id, ting_id)
VALUES (%element_id, "@ting_id")
  ON DUPLICATE KEY UPDATE
    ting_id = "@ting_id"
      ', array(
        '!table' => $this->table,
        '%element_id' => $element_id,
        '@ting_id' => $data['value'],
      ));

      return TRUE;
    }

    return FALSE;
  }

  /**
   * On element created.
   * @ignore
   */
  protected function onElementCreated($element_id, $list_id, $data) {
    if ($data['type'] == 'ting_object') {
      DB::q('
INSERT INTO !table
(element_id, ting_id)
VALUES (%element_id, "@ting_id")
  ON DUPLICATE KEY UPDATE
    ting_id = "@ting_id"
      ', array(
        '!table' => $this->table,
        '%element_id' => $element_id,
        '@ting_id' => $data['value'],
      ));

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Create the module table on install.
   * @ignore
   */
  protected function _install() {
    DB::q('
CREATE TABLE IF NOT EXISTS !table (
  element_id int(11) NOT NULL,
  ting_id char(32) NOT NULL,
  PRIMARY KEY (element_id),
  KEY ting_id (ting_id)
) ENGINE = InnoDB
    ', array('!table' => $this->table));

    return TRUE;
  }

  /**
   * Remove the module table on uninstall.
   * @ignore
   */
  protected function _uninstall() {
    DB::q('DROP TABLE IF EXISTS !table', array('!table' => $this->table));

    return TRUE;
  }
}

new TingQuery();
