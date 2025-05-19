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

$serialNumber = isset($_GET['serial_number']) ? filter_var($_GET['serial_number'], FILTER_SANITIZE_STRING) : '';

if (empty($serialNumber)) {
    echo json_encode(['success' => false, 'message' => 'Serial number is required.']);
    exit();
}

$sql = "SELECT
            i.box_no,
            i.item_desc_id,
            gd.mrep_id AS accountable_id,
            e.emp_fname AS accountable_fname,
            e.emp_lname AS accountable_lname
        FROM items i
        LEFT JOIN gadget_distribution gd ON i.item_id = gd.item_id
        LEFT JOIN employees e ON gd.mrep_id = e.emp_rec_id
        WHERE i.serial_no = :serial_number";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':serial_number', $serialNumber);
$stmt->execute();
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if ($item) {
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    echo json_encode(['success' => true, 'item' => null]); // Indicate no item found
}

$conn = null;
?>