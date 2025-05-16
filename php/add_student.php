<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'db.php';

// Get the JSON data from the request body
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true); // Decode the JSON into an associative array

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Error decoding JSON data.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentID = isset($data['studentID']) ? $conn->real_escape_string($data['studentID']) : null;
    $firstName = isset($data['first-name']) ? $conn->real_escape_string($data['first-name']) : null;
    $middleName = isset($data['middle-name']) ? $conn->real_escape_string($data['middle-name']) : null;
    $lastName = isset($data['last-name']) ? $conn->real_escape_string($data['last-name']) : null;
    $suffix = isset($data['suffix-name']) ? $conn->real_escape_string($data['suffix-name']) : null;
    $gender = isset($data['gender']) ? $conn->real_escape_string($data['gender']) : null;
    $section = isset($data['section']) ? $conn->real_escape_string($data['section']) : null;
    $departmentId = isset($data['department']) ? intval($data['department']) : null;
    $contactNumber = isset($data['contact_number']) ? $conn->real_escape_string($data['contact_number']) : null;
    $email = isset($data['email']) ? $conn->real_escape_string($data['email']) : null;
    $address = isset($data['address']) ? $conn->real_escape_string($data['address']) : null;

    $query = "INSERT INTO students (student_id, first_name, middle_name, last_name, suffix, gender, section, department_id, contact_number, email, stud_address)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssssisss", $studentID, $firstName, $middleName, $lastName, $suffix, $gender, $section, $departmentId, $contactNumber, $email, $address);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Student information added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding student information: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST is allowed.']);
}

$conn->close();
?>