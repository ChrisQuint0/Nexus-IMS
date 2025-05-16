<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
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
    echo json_encode(['success' => false, 'message' => 'Invalid data received. Expected an array of employee data.']);
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

$success = true;
$messages = [];

foreach ($data as $row) {
    $employee_id = sanitize_data($row['employee_id']);
    $emp_fname = sanitize_data($row['emp_fname']);
    $emp_lname = sanitize_data($row['emp_lname']);
    $emp_minit = sanitize_data($row['emp_minit']);
    $emp_suffix = sanitize_data($row['emp_suffix']);
    $emp_email = sanitize_email($row['emp_email']);
    $department_id = intval($row['department_id']);
    $emp_contact_number = sanitize_data($row['emp_contact_number']); // Store in a variable
    $emp_address = sanitize_data($row['emp_address']); // Store in a variable
    $emp_category = sanitize_data($row['emp_category']); // Store in a variable

    // Basic validation
    $errors = []; // Use a local array for each row's errors
    if (empty($employee_id)) {
        $errors[] = "Employee ID is required.";
    }
    if (empty($emp_fname)) {
        $errors[] = "First Name is required.";
    }
    if (empty($emp_lname)) {
        $errors[] = "Last Name is required.";
    }
    if (empty($emp_email)) {
        $errors[] = "Email is required.";
    }
    if ($department_id === 0) { // Changed from null to 0
        $errors[] = "Department ID is required.";
    }

    if (!empty($errors)) {
        $messages[] = [
            'employee_id' => $employee_id,
            'message' => "Validation errors: " . implode(" ", $errors)
        ];
        $success = false;
        continue; // Skip to the next row
    }

    $sql = "INSERT INTO employees (employee_id, emp_fname, emp_lname, emp_minit, emp_suffix, emp_email, department_id, emp_contact_number, emp_address, emp_category)
            VALUES (:employee_id, :emp_fname, :emp_lname, :emp_minit, :emp_suffix, :emp_email, :department_id, :emp_contact_number, :emp_address, :emp_category)";

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':emp_fname', $emp_fname);
    $stmt->bindParam(':emp_lname', $emp_lname);
    $stmt->bindParam(':emp_minit', $emp_minit);
    $stmt->bindParam(':emp_suffix', $emp_suffix);
    $stmt->bindParam(':emp_email', $emp_email);
    $stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
    $stmt->bindParam(':emp_contact_number', $emp_contact_number); // Pass the variable
    $stmt->bindParam(':emp_address', $emp_address); // Pass the variable
    $stmt->bindParam(':emp_category', $emp_category); // Pass the variable

    if (!$stmt->execute()) {
        $errorInfo = $stmt->errorInfo();
        $errorMessage = "Error inserting employee ID: $employee_id. ";
        switch ($errorInfo[1]) { // Use the error code
            case 1062:
                $errorMessage .= "Duplicate entry. Employee ID already exists.";
                break;
            // Add more specific error code handling as needed
            case 1452:
                $errorMessage .= "Cannot add or update a child row: a foreign key constraint fails.  This usually means the Department ID is invalid.";
                break;
            default:
                $errorMessage .= "Database error: " . $errorInfo[2];
        }
        $messages[] = [
            'employee_id' => $employee_id,
            'message' => $errorMessage,
            'error_code' => $errorInfo[1] // Include the error code for more detailed handling if needed
        ];
        $success = false;
    } else {
        $messages[] = [
            'employee_id' => $employee_id,
            'message' => "Employee ID: $employee_id inserted successfully"
        ];
    }
}

if ($success) {
    echo json_encode(['success' => true, 'message' => 'All employees added successfully.', 'individual_messages' => $messages]);
} else {
    echo json_encode(['success' => false, 'message' => 'Some employees were not added.', 'individual_messages' => $messages]);
}

$conn = null;
?>
