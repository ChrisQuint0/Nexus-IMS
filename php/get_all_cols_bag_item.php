<?php
include "db_connection_header.php";

// Fetch bag item records with item names and item_desc_id
$sql_bags = "SELECT
    bi.bag_item_id,
    bi.box_no,
    id.item_name,
    bi.serial_no,
    bi.purchase_date,
    bi.item_desc_id
FROM
    bag_items bi
JOIN
    item_desc id ON bi.item_desc_id = id.item_desc_id";

$bag_result = $conn->query($sql_bags);
$bags = [];
while ($row = $bag_result->fetch_assoc()) {
    $bags[] = $row;
}

// Fetch all bag item names for the dropdown (category_id = 3)
$sql_bag_names = "SELECT item_desc_id, item_name FROM item_desc WHERE category_id = 3";
$bag_names_result = $conn->query($sql_bag_names);
$bag_item_names = [];
while ($row = $bag_names_result->fetch_assoc()) {
    $bag_item_names[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['bags' => $bags, 'bag_item_names' => $bag_item_names]);

$conn->close();
?>