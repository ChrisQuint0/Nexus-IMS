<?php
include "db_connection_header.php";

$sql_items = "SELECT * FROM items";
$item_result = $conn->query($sql_items);
$items = [];
while ($row = $item_result->fetch_assoc()) {
    $items[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['items' => $items]);

$conn->close();
?>