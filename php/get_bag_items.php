<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");


// Database connection details (as before)
require_once 'db_functions.php';

// Get database connection
$conn = get_pdo_connection();

try {
    $conn = get_database_connection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
    exit();
}
//added item specs for receipt generation.  
$sql = "SELECT item_desc_id, item_name, item_specs
        FROM item_desc
        WHERE category_id = 3"; // 3 is the category_id for bags

$stmt = $conn->prepare($sql);
$stmt->execute();
$bags = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'bags' => $bags]);

$conn = null;
