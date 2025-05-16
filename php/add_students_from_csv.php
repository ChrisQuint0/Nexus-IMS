<?php
header('Content-Type: application/json');
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection details (replace with your actual credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nexus_ims_db_dummy";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Database connection failed: " . $e->getMessage()]);
    exit();
}

// Get the JSON data
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if ($data === null || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received. Expected an array of student data.']);
    exit();
}

// Function to sanitize data
function sanitize_data($value) {
    if ($value === null) return null;
    return filter_var($value, FILTER_SANITIZE_STRING);
}
// Function to sanitize email
function sanitize_email($value) {
    if ($value === null) return null;
    return filter_var($value, FILTER_SANITIZE_EMAIL);
}

$success = true; // Track overall success
$messages = [];    // Store individual messages

foreach ($data as $row) {
    // Sanitize the data
    $student_id = sanitize_data($row['student_id']);
    $first_name = sanitize_data($row['first_name']);
    $middle_name = sanitize_data($row['middle_name']);
    $last_name = sanitize_data($row['last_name']);
    $suffix = sanitize_data($row['suffix']);
    $gender = sanitize_data($row['gender']);
    $section = sanitize_data($row['section']);
    $contact_number = sanitize_data($row['contact_number']);
    $email = sanitize_email($row['email']);
    $stud_address = sanitize_data($row['stud_address']);
    $department_id = intval($row['department_id']); // Ensure it is an integer

    // Basic validation (customize as needed)
    if (empty($student_id) || empty($first_name) || empty($last_name) || $department_id === null) {
        $messages[] = "Missing required fields for student ID: $student_id";
        $success = false; // Set overall success to false
        continue;       // Skip to the next row
    }
    if ($gender !== null && $gender !== 'Male' && $gender !== 'Female'){
        $messages[] = "Invalid gender for student ID: $student_id";
        $success = false;
        continue;
    }

    // Prepare the SQL INSERT statement
    $sql = "INSERT INTO students (student_id, first_name, middle_name, last_name, suffix, gender, section, department_id, contact_number, email, stud_address)
            VALUES (:student_id, :first_name, :middle_name, :last_name, :suffix, :gender, :section, :department_id, :contact_number, :email, :stud_address)";

    $stmt = $conn->prepare($sql);

    // Bind the parameters
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':middle_name', $middle_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':suffix', $suffix);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':section', $section);
    $stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
    $stmt->bindParam(':contact_number', $contact_number);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':stud_address', $stud_address);

    // Execute the query
    if (!$stmt->execute()) {
        $errorInfo = $stmt->errorInfo(); // Get specific error info
        $messages[] = "Error inserting student ID: $student_id.  Error: " . $errorInfo[2];
        $success = false;
    } else {
         $messages[] = "Student ID: $student_id inserted successfully";
    }
}

if ($success) {
    echo json_encode(['success' => true, 'message' => 'All students added successfully.', 'individual_messages' => $messages]);
} else {
    echo json_encode(['success' => false, 'message' => 'Some students were not added.', 'individual_messages' => $messages]);
}

$conn = null;
?>