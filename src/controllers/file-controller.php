<?php
require_once __DIR__ . '/./base-controller.php';

class File_Controller extends Base_Controller
{

  public function getImage()
  {
    try {
      $storage_path = __DIR__ . '/../../';
      $image = $_GET['file_path'] ?? '';
      $file_path = "{$storage_path}{$image}";

      $real_base = realpath($storage_path);
      $real_file = realpath($file_path);

      // Check if the file exists and is within the allowed directory
      if ($real_file && strpos($real_file, $real_base) === 0 && file_exists($real_file)) {
        header("Content-Type: " . mime_content_type($real_file));
        readfile($real_file);
      } else {
        http_response_code(404);
        echo "File not found.";
      }
    } catch (Exception $e) {
      http_response_code(404);
      echo "File not found.";
    }
  }
}
