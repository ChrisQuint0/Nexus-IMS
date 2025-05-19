<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_functions.php';

// Get database connection
$conn = get_pdo_connection();

$conn = get_database_connection();

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"));

if (isset($data->categoryName) && !empty(trim($data->categoryName))) {
    $categoryName = trim($data->categoryName);

    $sql = "INSERT INTO categories (category_name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoryName);

    if ($stmt->execute()) {
        echo json_encode(["success" => "Category added successfully"]);
    } else {
        echo json_encode(["error" => "Error adding category: " . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Category name cannot be empty"]);
}

$conn->close();
?>