<?php

/**
 * @file
 * Handle preferences.
 */

class LocalAnalysis extends Module {
  public $version = 1;

  /**
   * The table.
   */
  private $table = 'm_local_analysis';

  /**
   * Abstract getEvents().
   */
  public function getEvents() {
    return array();
  }

  /**
   * The send data is saved for later analysis.
   *
   * @param {mixed} $data
   *   The data to save.
   *
   * @return {boolean}
   *   Saved or not.
   */
  public function sendData($data) {
    $result = DB::q('
INSERT INTO !table
(library_code, data)
VALUES ("@library_code", "@data")
    ', array(
      '!table' => $this->table,
      '@library_code' => $GLOBALS['library_code'],
      '@data' => serialize($data),
    ));

    return TRUE;
  }


  /**
   * Create the module table on install.
   */
  protected function _install() {
    DB::q('
CREATE TABLE IF NOT EXISTS !table (
  id int(11) NOT NULL auto_increment,
  library_code varchar(128) NOT NULL,
  ts timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data text NOT NULL
  PRIMARY KEY (id)
) ENGINE=InnoDB;
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

new LocalAnalysis();
