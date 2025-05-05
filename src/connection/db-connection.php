<?php

class Database_Connection
{
  private static $db = null;

  public static function getConnection()
  {
    if (self::$db === null) {
      $config = require __DIR__ . '/../config/db-config.php';

      try {
        // Build the connection string with database name, username, and password
        self::$db = new PDO(
          "mysql:host={$config['host_name']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
          $config['username'],
          $config['password']
        );

        // Set PDO to throw exceptions for errors
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Set default fetch mode to associative array
        self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Disable emulated prepares for security
        self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("A database connection error occurred: " . $e->getMessage());
      }
    }

    return self::$db;
  }
}
