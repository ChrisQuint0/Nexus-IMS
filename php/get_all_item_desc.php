<?php
include "db_connection_header.php";

$sql_item_desc = "SELECT item_desc_id, item_name FROM item_desc";
$item_desc_result = $conn->query($sql_item_desc);
$item_descriptions = [];
while ($row = $item_desc_result->fetch_assoc()) {
    $item_descriptions[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['item_descriptions' => $item_descriptions]);

$conn->close();
?>