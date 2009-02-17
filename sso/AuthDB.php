<?php

require_once('config.php');

class AuthDB {
  private $dbh;

  public function __construct() {
    $this->dbh = mysql_connect(SQL_HOST, SQL_USERNAME, SQL_PASSWORD);
    if (!$this->dbh) {
      throw new ErrorException(
        'Cannot connect to MySQL server: ' . mysql_error());
    }

    if (!mysql_select_db(SQL_DB, $this->dbh)) {
      throw new ErrorException(
        'Cannot select database: ' . mysql_error());
    }
  }

  public function __destruct() {
    mysql_close($this->dbh);
  }

  public function read($query) {
    $r = mysql_query($query, $this->dbh);
    if (!$r) {
      throw new ErrorException(
        'Failed to read from database: ' . mysql_error());
    }

    return mysql_fetch_assoc($r, $this->dbh);
  }

  public function write($query) {
    $r = mysql_query($query, $this->dbh);
    if (!$r) {
      throw new ErrorException(
        'Failed to write to database: ' . mysql_error());
    }

    return mysql_affected_rows($this->dbh);
  }
}
