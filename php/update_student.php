<?php
// update_student.php

session_start();
include "db_connection_header.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(array("error" => "User not authenticated."));
    $conn->close();
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

// Get the JSON data sent from the client
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if ($data === null || !isset($data['stud_rec_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(array("error" => "Invalid data received."));
    $conn->close();
    exit();
}

// Sanitize input data
$stud_rec_id = $conn->real_escape_string($data['stud_rec_id']);
$student_id = $conn->real_escape_string($data['student_id'] ?? '');
$first_name = $conn->real_escape_string($data['first_name'] ?? '');
$middle_name = $conn->real_escape_string($data['middle_name'] ?? null);
$last_name = $conn->real_escape_string($data['last_name'] ?? '');
$suffix = $conn->real_escape_string($data['suffix'] ?? null);
$gender = $conn->real_escape_string($data['gender'] ?? '');
$section = $conn->real_escape_string($data['section'] ?? '');
$contact_number = $conn->real_escape_string($data['contact_number'] ?? null);
$email = $conn->real_escape_string($data['email'] ?? null);
$stud_address = $conn->real_escape_string($data['address'] ?? null);
$department_id = $conn->real_escape_string($data['department_id'] ?? null); // Get the department_id

// Construct the UPDATE query
$sql = "UPDATE students SET
            student_id = '$student_id',
            first_name = '$first_name',
            middle_name = " . ($middle_name === null ? 'NULL' : "'$middle_name'") . ",
            last_name = '$last_name',
            suffix = " . ($suffix === null ? 'NULL' : "'$suffix'") . ",
            gender = '$gender',
            section = '$section',
            contact_number = " . ($contact_number === null ? 'NULL' : "'$contact_number'") . ",
            email = " . ($email === null ? 'NULL' : "'$email'") . ",
            stud_address = " . ($stud_address === null ? 'NULL' : "'$stud_address'") . ",
            department_id = " . ($department_id === null ? 'NULL' : "'$department_id'") . "
WHERE stud_rec_id = $stud_rec_id";

if ($conn->query($sql) === TRUE) {
    $department_name = "N/A"; // Default value if department_id is null or not found

    if ($department_id !== null) {
        $deptSql = "SELECT department_name FROM departments WHERE department_id = $department_id";
        $deptResult = $conn->query($deptSql);

        if ($deptResult && $deptResult->num_rows > 0) {
            $deptRow = $deptResult->fetch_assoc();
            $department_name = $deptRow['department_name'];
        }
    }

  $action = "Student record with ID: $student_id was updated. Updated To: " .
          "First Name: " . ($first_name ?? 'N/A') . ", " .
          "Middle Name: " . ($middle_name ?? 'N/A') . ", " .
          "Last Name: " . ($last_name ?? 'N/A') . ", " .
          "Suffix: " . ($suffix ?? 'N/A') . ", " .
          "Gender: " . ($gender ?? 'N/A') . ", " .
          "Section: " . ($section ?? 'N/A') . ", " .
          "Contact Number: " . ($contact_number ?? 'N/A') . ", " .
          "Email: " . ($email ?? 'N/A') . ", " .
          "Address: " . ($stud_address ?? 'N/A') . ", " .
          "Department Name: " . ($department_name ?? 'N/A');
    $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$action')";
    if ($conn->query($logSql) === TRUE) {
        http_response_code(200);
        echo json_encode(array("message" => "Student record updated and activity logged."));
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Student record updated, but failed to log activity: " . $conn->error));
    }
} else {
    http_response_code(500);
    echo json_encode(array("error" => "Error updating record: " . $conn->error));
}

$conn->close();

?>