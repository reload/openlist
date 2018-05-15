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
class Query extends Module {
  /**
   * Module version.
   * @ignore
   */
  public $version = 1;

  /**
   * The table.
   */
  private $table = 'm_query';

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
   * Get lists the specific value is added to.
   *
   * @param string $value
   *   The ting object id.
   * @param array $list_types
   *   List types.
   *
   * @return array
   *   List IDs where the ting object is present.
   */
  public function getLists($value, $list_types) {
    $result = DB::q('
SELECT
  DISTINCT e.list_id
FROM
  elements e
  JOIN !table tq ON (e.element_id = tq.element_id)
  JOIN lists l ON (l.list_id = e.list_id)
WHERE
  tq.value = "@value"
  AND l.library_code IN (?$library_access)
  AND l.type IN (?$list_types)
ORDER BY
  e.list_id
    ', array(
      '!table' => $this->table,
      '?$list_types' => $list_types,
      '@value' => $value,
      '?$library_access' => $GLOBALS['library_access'],
    ));

    $buffer = array();

    while ($row = $result->fetch_assoc()) {
      $buffer[] = $row['list_id'];
    }

    return $buffer;
  }

  /**
   * Rebuild the Query table.
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
      if (empty($data['value'])) {
        continue;
      }

      $data = unserialize($row['data']);
      DB::q('
INSERT INTO !table
(element_id, value)
VALUES (%element_id, "@value")
ON DUPLICATE KEY UPDATE
  value = "@value"
      ', array(
        '!table' => $this->table,
        '%element_id' => $row['element_id'],
        '@value' => $data['value'],
      ));
    }

    return TRUE;
  }

  /**
   * On element deleted.
   * @ignore
   */
  protected function onDeleteElement($element_ids) {
    foreach ($element_ids as $element_id) {
      DB::q('
  DELETE FROM !table
  WHERE
    element_id = %element_id
      ', array(
        '!table' => $this->table,
        '%element_id' => $element_id,
      ));
    }

    return TRUE;
  }

  /**
   * On element edited.
   * @ignore
   */
  protected function onEditElement($element_id, $data) {
      DB::q('
INSERT INTO !table
(element_id, value)
VALUES (%element_id, "@value")
  ON DUPLICATE KEY UPDATE
    value = "@value"
      ', array(
        '!table' => $this->table,
        '%element_id' => $element_id,
        '@value' => $data['value'],
      ));

      return TRUE;

    return FALSE;
  }

  /**
   * On element created.
   * @ignore
   */
  protected function onElementCreated($element_id, $list_id, $data) {
    DB::q('
INSERT INTO !table
(element_id, value)
VALUES (%element_id, "@value")
ON DUPLICATE KEY UPDATE
  value = "@value"
    ', array(
      '!table' => $this->table,
      '%element_id' => $element_id,
      '@value' => $data['value'],
    ));

    return TRUE;

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
  value char(32) NOT NULL,
  PRIMARY KEY (element_id),
  KEY value (value)
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

new Query();
