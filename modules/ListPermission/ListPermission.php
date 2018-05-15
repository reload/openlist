<?php

/**
 * @file
 * Handle list permissions.
 */

class ListPermission extends Module {
  public $version = 1;

  /**
   * The table.
   */
  private $table = 'm_list_permission';

  /**
   * Abstract getEvents().
   */
  public function getEvents() {
    return array(
      'createList' => 'onListCreate',
      'editList' => 'onListEdit',
    );
  }

  public function listPermissions($list_id) {
    $permissions = [];

    $result = DB::q('
SELECT user, permission
FROM m_list_user_permission
WHERE
list_id = %list_id
    ', array(
      '%list_id' => $list_id,
    ));

    while ($row = $result->fetch_assoc()) {
      $permissions[$row->user] = $row;
    }

    return $permissions;
  }

  public function removePermission($user, $list_id) {
    DB::q("
DELETE FROM m_list_user_permission
WHERE
  user = '@user'
  AND list_id = %list_id
    ", array(
      '@user' => $user,
      '%list_id' => $list_id,
    ));
  }

  public function setPermission($user, $list_id, $permission) {
    DB::q("
INSERT INTO m_list_user_permission
(user, list_id, permission)
VALUES ('@user', %list_id, '@permission')
ON DUPLICATE KEY UPDATE permission = '@permission'
    ", array(
      '@user' => $user,
      '%list_id' => $list_id,
      '@permission' => $permission,
    ));
  }

  public function getPermission($user, $list_id) {
    $result = DB::q("
SELECT permission
FROM m_list_user_permission
WHERE
  user = '@user'
  AND list_id = %list_id
LIMIT 1
    ", array(
      '@user' => $user,
      '%list_id' => $list_id,
    ))->fetch_assoc();

    if ($result) {
      return $result;
    }

    return FALSE;
  }

  /**
   * Get public lists.
   */
  public function getPublicLists($title = '') {
    $result = array();

    $title_where = '';
    if (!empty($title)) {
      $title_where = 'AND l.title LIKE "%@title%"';
    }

    $lists = DB::q('
SELECT l.list_id, l.type, l.title, l.modified, l.owner, l.data
FROM lists l JOIN !table lp ON (lp.list_id = l.list_id)
WHERE
  l.library_code IN (?$library_access)
  AND lp.permission = "public"
  AND l.status = 1
  ' . $title_where . '
    ', array(
      '!table' => $this->table,
      '@title' => $title,
      '?$library_access' => $GLOBALS['library_access'],
    ));

    while ($list = $lists->fetch_assoc()) {
      $result[$list['list_id']] = OpenList::createListData($list);
    }

    return $result;
  }

  /**
   * On list edited.
   */
  protected function onListEdit($list_id, $title, $data) {
    if (!empty($data['fields'])) {
      foreach ($data['fields'] as $field) {
        if ($field['name'] == 'field_ding_list_status') {
          DB::q('
INSERT INTO !table
(list_id, permission)
VALUES (%list_id, "@permission")
  ON DUPLICATE KEY UPDATE
    permission = "@permission"
          ', array(
            '!table' => $this->table,
            '@permission' => $field['value'],
            '%list_id' => $list_id,
          ));
        }
      }
    }

    // V2 handling.
    if (!empty($data['visibility'])) {
      DB::q('
INSERT INTO !table
(list_id, permission)
VALUES (%list_id, "@permission")
ON DUPLICATE KEY UPDATE
permission = "@permission"
      ', array(
        '!table' => $this->table,
        '@permission' => $data['visibility'],
        '%list_id' => $list_id,
      ));
    }

    return TRUE;
  }

  /**
   * On list created.
   */
  protected function onListCreate($insert_id, $owner, $title, $data) {
    if (!empty($data['fields'])) {
      foreach ($data['fields'] as $field) {
        if ($field['name'] == 'field_ding_list_status') {
          DB::q('
INSERT INTO !table
(list_id, permission)
VALUES (%list_id, "@permission")
  ON DUPLICATE KEY UPDATE
    permission = "@permission"
          ', array(
            '!table' => $this->table,
            '@permission' => $field['value'],
            '%list_id' => $insert_id,
          ));
        }
      }
    }

    // V2 handling.
    if (!empty($data['visibility'])) {
      DB::q('
INSERT INTO !table
(list_id, permission)
VALUES (%list_id, "@permission")
ON DUPLICATE KEY UPDATE
permission = "@permission"
      ', array(
        '!table' => $this->table,
        '@permission' => $data['visibility'],
        '%list_id' => $list_id,
      ));
    }

    return TRUE;
  }

  /**
   * Create the module table on install.
   */
  protected function _install() {
    DB::q('
CREATE TABLE IF NOT EXISTS m_list_permission (
  permission_id int(11) NOT NULL AUTO_INCREMENT,
  list_id int(11) NOT NULL,
  permission enum("private","shared","public") NOT NULL,
  PRIMARY KEY (permission_id),
  KEY list_id (list_id)
) ENGINE=InnoDB;
    ', array('!table' => $this->table));

    DB::q('
CREATE TABLE IF NOT EXISTS m_list_user_permission (
  user varchar(128) NOT NULL,
  list_id int(11) NOT NULL,
  permission varchar(32) NOT NULL,
  PRIMARY KEY (user, list_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    ');

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

new ListPermission();
