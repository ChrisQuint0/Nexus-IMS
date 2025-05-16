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

$itemDescId = $_GET['id'];

$sql = "SELECT item_specs FROM item_desc WHERE item_desc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $itemDescId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  echo json_encode($row);
} else {
  echo json_encode(["item_specs" => ""]);
}

$stmt->close();
$conn->close();
?>