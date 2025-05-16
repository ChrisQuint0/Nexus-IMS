<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$host = "localhost";
$username = "root";
$password = "";
$database = "nexus_ims_db_dummy";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
  die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$sql = "SELECT item_desc_id, item_name FROM item_desc";
$result = $conn->query($sql);

$itemDescriptions = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $itemDescriptions[] = $row;
  }
}

$conn->close();
echo json_encode($itemDescriptions);
?>