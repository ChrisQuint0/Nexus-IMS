<?php
$host = 'localhost';
$db = 'nexus_ims_db_dummy';
$user = 'root';
$pass = '';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}


/**
 * Function to get available gadget types for dropdown population
 * This could be used to dynamically generate dropdown options
 */
function get_gadget_types($conn)
{
  $query = "SELECT DISTINCT item_name FROM item_desc ORDER BY item_name";
  $result = $conn->query($query);
  $types = [];

  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $types[] = $row['item_name'];
    }
  }

  return $types;
}
