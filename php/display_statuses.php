<?php
session_start();
include 'db_connection_header.php'; // Assuming you have this for database connection

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
        $sql = "SELECT status_id, status_name FROM statuses";
        $result = $conn->query($sql);
        $statuses = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $statuses[] = $row;
            }
            echo json_encode(['success' => true, 'statuses' => $statuses]);
        } else {
            echo json_encode(['success' => true, 'statuses' => []]); // No statuses found
        }
        break;

    case 'search':
        $name = $conn->real_escape_string($_GET['name']);
        $sql = "SELECT status_id, status_name FROM statuses WHERE status_name LIKE '%$name%'";
        $result = $conn->query($sql);
        $statuses = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $statuses[] = $row;
            }
            echo json_encode(['success' => true, 'statuses' => $statuses]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No statuses found matching your search.']);
        }
        break;

    case 'add':
        $name = $conn->real_escape_string($_POST['name']);
        if (!empty($name)) {
            $sql = "INSERT INTO statuses (status_name) VALUES ('$name')";
            if ($conn->query($sql) === TRUE) {
                $logAction = "Added status: " . $name;
                $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
                $conn->query($logSql); // Log the action
                echo json_encode(['success' => true, 'message' => 'Status added successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => "Error adding status: " . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Status name cannot be empty.']);
        }
        break;

    case 'edit':
        $id = $conn->real_escape_string($_POST['id']);
        $name = $conn->real_escape_string($_POST['name']);
        if (!empty($name)) {
            // Fetch original status name for logging
            $selectSql = "SELECT status_name FROM statuses WHERE status_id = $id";
            $originalResult = $conn->query($selectSql);
            $originalName = '';
            if ($originalResult && $originalResult->num_rows > 0) {
                $row = $originalResult->fetch_assoc();
                $originalName = $row['status_name'];
            }

            $sql = "UPDATE statuses SET status_name = '$name' WHERE status_id = $id";
            if ($conn->query($sql) === TRUE) {
                $logAction = "Updated status with ID: $id. Updated To: Name: $name (Original Name: $originalName)";
                $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
                $conn->query($logSql); // Log the action
                echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => "Error updating status: " . $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Status name cannot be empty.']);
        }
        break;

    case 'delete':
        $id = $conn->real_escape_string($_POST['id']);

        // Fetch status name before deletion for logging
        $selectSql = "SELECT status_name FROM statuses WHERE status_id = $id";
        $deleteResult = $conn->query($selectSql);
        $deletedName = '';
        if ($deleteResult && $deleteResult->num_rows > 0) {
            $row = $deleteResult->fetch_assoc();
            $deletedName = $row['status_name'];
        }

        $sql = "DELETE FROM statuses WHERE status_id = $id";
        if ($conn->query($sql) === TRUE) {
            $logAction = "Deleted status with ID: $id (Name: $deletedName)";
            $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
            $conn->query($logSql); // Log the action
            echo json_encode(['success' => true, 'message' => 'Status deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => "Error deleting status: " . $conn->error]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

// Close the database connection
$conn->close();
?>