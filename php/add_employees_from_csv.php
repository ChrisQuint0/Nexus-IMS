<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection details (replace with your actual credentials)
require_once 'db_functions.php';

// Get database connection
$conn = get_pdo_connection();

try {
    $conn = get_database_connection();
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
        $errors[] = "Employee ID field is missing or empty";
    }
    if (empty($emp_fname)) {
        $errors[] = "First Name field is missing or empty";
    }
    if (empty($emp_lname)) {
        $errors[] = "Last Name field is missing or empty";
    }
    if (empty($emp_email)) {
        $errors[] = "Email field is missing or empty";
    }
    if ($department_id === 0) { // Changed from null to 0
        $errors[] = "Department ID field is missing or empty";
    }

    if (!empty($errors)) {
        $messages[] = [
            'employee_id' => $employee_id,
            'message' => "⚠️ Validation Failed: " . implode(", ", $errors)
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

    // Execute the query
    if (!$stmt->execute()) {
        $errorInfo = $stmt->errorInfo();
        $messages[] = [
            'employee_id' => $employee_id,
            'message' => "⚠️ Database Error: " . $errorInfo[2] . ". Please check the data and try again."
        ];
        $success = false;
    } else {
        $messages[] = [
            'employee_id' => $employee_id,
            'message' => "✅ Employee successfully added to the system"
        ];

        // Log the employee addition
        $logAction = "Added employee via CSV import - Employee ID: $employee_id, " .
                    "Name: $emp_fname " . ($emp_minit ? "$emp_minit " : "") . "$emp_lname" .
                    ($emp_suffix ? " $emp_suffix" : "") . ", " .
                    "Email: " . ($emp_email ?? 'N/A') . ", " .
                    "Category: " . ($emp_category ?? 'N/A') . ", " .
                    "Department ID: " . ($department_id ?? 'N/A');

        $logSql = "INSERT INTO audit_log (user_id, action) VALUES (:user_id, :action)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $logStmt->bindParam(':action', $logAction);
        $logStmt->execute();
    }
}

if ($success) {
    echo json_encode(['success' => true, 'message' => '✅ Upload Complete: All employees have been successfully added to the system.', 'individual_messages' => $messages]);
} else {
    echo json_encode(['success' => false, 'message' => '⚠️ Upload Partially Complete: Some employees could not be added. Please review the details below.', 'individual_messages' => $messages]);
}

$conn = null;
?>
