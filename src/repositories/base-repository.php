<?php
require_once __DIR__ .  '/../connection/db-connection.php';

class Base_Repository
{
  protected $db;

  public function __construct()
  {
    $this->db = Database_Connection::getConnection();
  }

  public function startTransaction()
  {
    $this->db->beginTransaction();
  }

  public function inTransaction()
  {
    return $this->db->inTransaction();
  }

  public function commitTransaction()
  {
    $this->db->commit();
  }

  public function rollbackTransaction()
  {
    $this->db->rollBack();
  }
}
