<?php

session_start(); // Start the session if it's not already started
include "db_connection_header.php";

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(array("error" => "User not authenticated."));
    $conn->close();
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

// Check if the stud_rec_id is provided in the GET request
if (!isset($_GET['stud_rec_id']) || empty($_GET['stud_rec_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(array("error" => "Student record ID is required."));
    $conn->close();
    exit();
}

// Sanitize the student record ID
$stud_rec_id = $conn->real_escape_string($_GET['stud_rec_id']);

// First, let's fetch the student's information before deleting for a more informative log
$selectSql = "SELECT student_id, first_name, middle_name, last_name FROM students WHERE stud_rec_id = $stud_rec_id";
$result = $conn->query($selectSql);

if ($result && $result->num_rows > 0) {
    $studentData = $result->fetch_assoc();
    $studentIdentifier = !empty($studentData['student_id']) ? $studentData['student_id'] : "Record ID: $stud_rec_id";
    $studentName = trim(($studentData['first_name'] ?? '') . ' ' . ($studentData['middle_name'] ?? '') . ' ' . ($studentData['last_name'] ?? ''));
    $logAction = "Deleted student record: $studentIdentifier";
    if (!empty($studentName)) {
        $logAction .= " (Name: $studentName)";
    }

    // Construct the DELETE query
    $sql = "DELETE FROM students WHERE stud_rec_id = $stud_rec_id";

    if ($conn->query($sql) === TRUE) {
        // Log the deletion activity
        $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
        if ($conn->query($logSql) === TRUE) {
            http_response_code(200); // OK
            echo json_encode(array("message" => "Student record deleted successfully and activity logged."));
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(array("message" => "Student record deleted successfully, but failed to log activity: " . $conn->error));
        }
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "Error deleting record: " . $conn->error));
    }
} else {
    http_response_code(404); // Not Found
    echo json_encode(array("error" => "Student record not found with ID: $stud_rec_id"));
}

$conn->close();

?>