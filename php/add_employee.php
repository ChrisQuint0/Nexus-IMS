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
  // Set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Get the JSON data from the request body
  $json_data = file_get_contents("php://input");
  $data = json_decode($json_data, true);

  if ($data === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data received.']);
    exit();
  }

  // Sanitize and validate the input data
  $employee_id = isset($data['employee_id']) ? trim($data['employee_id']) : '';
  $emp_category = isset($data['emp-category']) ? trim($data['emp-category']) : '';
  $emp_fname = isset($data['emp_fname']) ? trim($data['emp_fname']) : '';
  $emp_lname = isset($data['emp_lname']) ? trim($data['emp_lname']) : '';
  $emp_minit = isset($data['emp_minit']) ? trim($data['emp_minit']) : ''; // Corrected key name
  $emp_suffix = isset($data['emp_suffix']) ? trim($data['emp_suffix']) : ''; // Corrected key name
  $emp_email = isset($data['emp_email']) ? trim($data['emp_email']) : '';
  $emp_department = isset($data['emp_department']) ? trim($data['emp_department']) : '';
  $emp_contact = isset($data['emp_contact_number']) ? trim($data['emp_contact_number']) : '';
  $emp_address = isset($data['emp_address']) ? trim($data['emp_address']) : '';

  // Basic validation (add more robust validation as needed)
  $errors = [];

  if (empty($employee_id)) {
    $errors[] = 'Employee ID is required.';
  }
  if (empty($emp_category) || !in_array($emp_category, ['Teaching Staff', 'Non-Teaching Staff'])) {
    $errors[] = 'Invalid employee category.';
  }
  if (empty($emp_fname)) {
    $errors[] = 'First name is required.';
  }
  if (empty($emp_lname)) {
    $errors[] = 'Last name is required.';
  }
  if (empty($emp_email) || !filter_var($emp_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address.';
  }
  if (empty($emp_department)) {
    $errors[] = 'Department is required.';
  }
  if (empty($emp_contact)) {
    $errors[] = 'Contact number is required.';
  }
  if (empty($emp_address)) {
    $errors[] = 'Address is required.';
  }

  if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => 'Validation errors.', 'errors' => $errors]);
    exit();
  }

  // Prepare the SQL INSERT statement
  $sql = "INSERT INTO employees (employee_id, emp_category, emp_fname, emp_lname, emp_minit, emp_suffix, emp_email, emp_contact_number, emp_address, department_id)
          VALUES (:employee_id, :emp_category, :emp_fname, :emp_lname, :emp_minit, :emp_suffix, :emp_email, :emp_contact, :emp_address, (SELECT department_id FROM departments WHERE department_name = :emp_department))";

  $stmt = $conn->prepare($sql);

  // Bind the parameters
  $stmt->bindParam(':employee_id', $employee_id);
  $stmt->bindParam(':emp_category', $emp_category);
  $stmt->bindParam(':emp_fname', $emp_fname);
  $stmt->bindParam(':emp_lname', $emp_lname);
  $stmt->bindParam(':emp_minit', $emp_minit);
  $stmt->bindParam(':emp_suffix', $emp_suffix);
  $stmt->bindParam(':emp_email', $emp_email);
  $stmt->bindParam(':emp_contact', $emp_contact);
  $stmt->bindParam(':emp_address', $emp_address);
  $stmt->bindParam(':emp_department', $emp_department);

  // Execute the query
  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Employee information added successfully.']);
  } else {
    // *IMPORTANT*:  Detailed error logging from the database
    $errorInfo = $stmt->errorInfo();
    echo json_encode([
        'success' => false,
        'message' => 'Error adding employee information.',
        'errorInfo' => $errorInfo, // Include the error information
        'sql' => $sql, // Include the SQL query
        'data' => $data // Include the data being used in the query
    ]);
  }

} catch(PDOException $e) {
  echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn = null;
?>
