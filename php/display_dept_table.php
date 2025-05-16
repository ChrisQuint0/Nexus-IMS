<?php
session_start();
include 'db_connection_header.php';

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
        $sql = "SELECT department_id, department_name FROM departments";
        $result = $conn->query($sql);
        $departments = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $departments[] = $row;
            }
            echo json_encode(['success' => true, 'departments' => $departments]);
        } else {
            echo json_encode(['success' => true, 'departments' => []]); // No departments found
        }
        break;

    case 'search':
        $name = $conn->real_escape_string($_GET['name']);
        $sql = "SELECT department_id, department_name FROM departments WHERE department_name LIKE '%$name%'";
        $result = $conn->query($sql);
        $departments = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $departments[] = $row;
            }
            echo json_encode(['success' => true, 'departments' => $departments]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No departments found matching your search.']);
        }
        break;

    case 'add':
        $name = $conn->real_escape_string($_POST['name']);
        if (!empty($name)) {
            $sql = "INSERT INTO departments (department_name) VALUES ('$name')";
            if ($conn->query($sql) === TRUE) {
                $logAction = "Added department: " . $name;
                $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
                $conn->query($logSql); // Log the action
                echo json_encode(['success' => true, 'message' => 'Department added successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => "Error adding department: " . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Department name cannot be empty.']);
        }
        break;

    case 'edit':
        $id = $conn->real_escape_string($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        if (!empty($name)) {
            // Fetch original department name for logging
            $selectSql = "SELECT department_name FROM departments WHERE department_id = $id";
            $originalResult = $conn->query($selectSql);
            $originalName = '';
            if ($originalResult && $originalResult->num_rows > 0) {
                $row = $originalResult->fetch_assoc();
                $originalName = $row['department_name'];
            }

            $sql = "UPDATE departments SET department_name = '$name' WHERE department_id = $id";
            if ($conn->query($sql) === TRUE) {
                $logAction = "Updated department with ID: $id. Updated To: Name: $name (Original Name: $originalName)";
                $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
                $conn->query($logSql); // Log the action
                echo json_encode(['success' => true, 'message' => 'Department updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => "Error updating department: " . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Department name cannot be empty.']);
        }
        break;

    case 'delete':
        $id = $conn->real_escape_string($_POST['id']);

        // Fetch department name before deletion for logging
        $selectSql = "SELECT department_name FROM departments WHERE department_id = $id";
        $deleteResult = $conn->query($selectSql);
        $deletedName = '';
        if ($deleteResult && $deleteResult->num_rows > 0) {
            $row = $deleteResult->fetch_assoc();
            $deletedName = $row['department_name'];
        }

        $sql = "DELETE FROM departments WHERE department_id = $id";
        if ($conn->query($sql) === TRUE) {
            $logAction = "Deleted department with ID: $id (Name: $deletedName)";
            $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
            $conn->query($logSql); // Log the action
            echo json_encode(['success' => true, 'message' => 'Department deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => "Error deleting department: " . $conn->error]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

// Close the database connection
$conn->close();
?>