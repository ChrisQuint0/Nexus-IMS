<?php
session_start();
include 'db_connection_header.php'; // Assuming you have this for database connection

// Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]));
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    $conn->close();
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

// Set response content type to JSON
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'load':
        $sql = "SELECT category_id, category_name FROM categories";
        $result = $conn->query($sql);
        $categories = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            echo json_encode(['success' => true, 'categories' => $categories]);
        } else {
            echo json_encode(['success' => true, 'categories' => []]); // No categories found
        }
        break;

    case 'search':
        $name = $conn->real_escape_string($_GET['name']);
        $sql = "SELECT category_id, category_name FROM categories WHERE category_name LIKE '%$name%'";
        $result = $conn->query($sql);
        $categories = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            echo json_encode(['success' => true, 'categories' => $categories]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No categories found matching your search.']);
        }
        break;

    case 'add':
        $name = $conn->real_escape_string($_POST['name']);
        if (!empty($name)) {
            $sql = "INSERT INTO categories (category_name) VALUES ('$name')";
            if ($conn->query($sql) === TRUE) {
                $logAction = "Added category: " . $name;
                $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
                $conn->query($logSql); // Log the action
                echo json_encode(['success' => true, 'message' => 'Category added successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => "Error adding category: " . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Category name cannot be empty.']);
        }
        break;

    case 'edit':
        $id = $conn->real_escape_string($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        if (!empty($name)) {
            // Fetch original category name for logging
            $selectSql = "SELECT category_name FROM categories WHERE category_id = $id";
            $originalResult = $conn->query($selectSql);
            $originalName = '';
            if ($originalResult && $originalResult->num_rows > 0) {
                $row = $originalResult->fetch_assoc();
                $originalName = $row['category_name'];
            }

            $sql = "UPDATE categories SET category_name = '$name' WHERE category_id = $id";
            if ($conn->query($sql) === TRUE) {
                $logAction = "Updated category with ID: $id. Updated To: Name: $name (Original Name: $originalName)";
                $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
                $conn->query($logSql); // Log the action
                echo json_encode(['success' => true, 'message' => 'Category updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => "Error updating category: " . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Category name cannot be empty.']);
        }
        break;

    case 'delete':
        $id = $conn->real_escape_string($_POST['id']);

        // Fetch category name before deletion for logging
        $selectSql = "SELECT category_name FROM categories WHERE category_id = $id";
        $deleteResult = $conn->query($selectSql);
        $deletedName = '';
        if ($deleteResult && $deleteResult->num_rows > 0) {
            $row = $deleteResult->fetch_assoc();
            $deletedName = $row['category_name'];
        }

        $sql = "DELETE FROM categories WHERE category_id = $id";
        if ($conn->query($sql) === TRUE) {
            $logAction = "Deleted category with ID: $id (Name: $deletedName)";
            $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
            $conn->query($logSql); // Log the action
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => "Error deleting category: " . $conn->error]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

// Close the database connection
$conn->close();
?>