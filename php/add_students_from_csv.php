<?php
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
    echo json_encode(['success' => false, 'message' => "⚠️ Database Connection Error: Unable to connect to the database. Technical details: " . $e->getMessage()]);
    exit();
}

// Get the JSON data
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if ($data === null || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => '⚠️ Invalid Data Format: The uploaded data is not in the expected format. Please ensure you are using the correct CSV template.']);
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
$messages = [];     // Store individual messages

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
        $messages[] = "⚠️ Missing Required Fields: Student ID $student_id is missing required information. Please check all mandatory fields.";
        $success = false; // Set overall success to false
        continue;       // Skip to the next row
    }
    if ($gender !== null && $gender !== 'Male' && $gender !== 'Female'){
        $messages[] = "⚠️ Invalid Gender Value: Student ID $student_id has an invalid gender value. Please use only 'Male' or 'Female'.";
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
        $messages[] = "⚠️ Database Error for Student ID $student_id: " . $errorInfo[2] . ". Please check the data and try again.";
        $success = false;
    } else {
        $messages[] = "✅ Student ID $student_id: Successfully added to the system";
        
        // Log the student addition
        $logAction = "Added student via CSV import - Student ID: $student_id, " .
                    "Name: $first_name " . ($middle_name ? "$middle_name " : "") . "$last_name" .
                    ($suffix ? " $suffix" : "") . ", " .
                    "Gender: " . ($gender ?? 'N/A') . ", " .
                    "Section: " . ($section ?? 'N/A') . ", " .
                    "Department ID: " . ($department_id ?? 'N/A');

        $logSql = "INSERT INTO audit_log (user_id, action) VALUES (:user_id, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $logStmt->bindParam(':action', $logAction);
        $logStmt->execute();
    }
}

if ($success) {
    echo json_encode(['success' => true, 'message' => '✅ Upload Complete: All students have been successfully added to the system.', 'individual_messages' => $messages]);
} else {
    echo json_encode(['success' => false, 'message' => '⚠️ Upload Partially Complete: Some students could not be added. Please review the details below.', 'individual_messages' => $messages]);
}

$conn = null;
?>