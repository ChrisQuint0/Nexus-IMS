<?php
session_start();
header('Content-Type: application/json');
include 'db.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $borrower_type = $_POST['borrower_type'] ?? null;
    $receiver_id_input = $_POST['receiver_id'] ?? null; // The ID entered in the UI
    $serial_no = $_POST['serial_no'] ?? null;
    $mrep_id = $_POST['accountable-select'] ?? null; // ID of the accountable employee
    $received_date = $_POST['date'] ?? null;
    $has_bag = isset($_POST['wBagCheckbox']) && $_POST['wBagCheckbox'] === '1';
    $bag_item_desc_id = $_POST['bag-type'] ?? null;
    $photo = $_FILES['image_uploads'] ?? null;

    $stud_rec_id = null;
    $emp_rec_id = null;
    $laptop_item_id = null;
    $bag_item_id = null;
    $photo_path = null;

    // 1. Determine borrower's record ID
    if ($borrower_type === 'student') {
        $stmt = $conn->prepare("SELECT stud_rec_id FROM students WHERE student_id = ?");
        $stmt->bind_param("s", $receiver_id_input);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $stud_rec_id = $row['stud_rec_id'];
            $receiver_id = $receiver_id_input;
        } else {
            $response['message'] = "Student with ID '$receiver_id_input' not found.";
            echo json_encode($response);
            exit();
        }
        $stmt->close();
    } else if ($borrower_type === 'employee') {
        $stmt = $conn->prepare("SELECT emp_rec_id FROM employees WHERE employee_id = ?");
        $stmt->bind_param("s", $receiver_id_input);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $emp_rec_id = $row['emp_rec_id'];
            $receiver_id = $receiver_id_input;
        } else {
            $response['message'] = "Employee with ID '$receiver_id_input' not found.";
            echo json_encode($response);
            exit();
        }
        $stmt->close();
    } else {
        $response['message'] = "Invalid borrower type.";
        echo json_encode($response);
        exit();
    }

    // 2. Determine the item ID for the gadget (non-bag)
    $stmt = $conn->prepare("SELECT i.item_id, idesc.item_name FROM items i 
        JOIN item_desc idesc ON i.item_desc_id = idesc.item_desc_id 
        JOIN categories c ON idesc.category_id = c.category_id 
        WHERE i.serial_no = ? AND c.category_name != 'Bag'");
    $stmt->bind_param("s", $serial_no);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $laptop_item_id = $row['item_id'];
        $laptop_desc_name = $row['item_name'];
    } else {
        $response['message'] = "Gadget with serial number '$serial_no' not found or is a bag.";
        echo json_encode($response);
        exit();
    }
    $stmt->close();

    // Create necessary directories if they don't exist
    $borrowers_photo_dir = "../assets/borrowers-photo/";
    $receipts_dir = "../assets/receipts/";

    if (!file_exists($borrowers_photo_dir)) {
        mkdir($borrowers_photo_dir, 0755, true);
    }
    if (!file_exists($receipts_dir)) {
        mkdir($receipts_dir, 0755, true);
    }

    // Handle photo upload
    if (isset($_FILES['image_uploads']) && $_FILES['image_uploads']['error'] === UPLOAD_ERR_OK) {
        $photo = $_FILES['image_uploads'];
        $filename = uniqid() . '_' . date('Ymd_His') . '.jpg';
        $destination = $borrowers_photo_dir . $filename;
        
        if (move_uploaded_file($photo['tmp_name'], $destination)) {
            $photo_path = $destination;
        } else {
            $response['message'] = "Error uploading photo.";
            echo json_encode($response);
            exit();
        }
    }

    // Generate receipt path
    $receipt_filename = uniqid() . '_receipt_' . date('Ymd_His') . '.pdf';
    $receipt_path = $receipts_dir . $receipt_filename;

    // Initialize these variables here, outside the conditional block
    $return_date = NULL;
    $status_id = 3;
    $repair_reason = NULL;
    $toBeReturned = 0;

    // 4. Update the gadget_distribution record for the main gadget
    $update_query = "
        UPDATE gadget_distribution SET
            borrower_type   = ?,
            stud_rec_id     = ?,
            receiver_id     = ?,
            mrep_id         = ?,
            received_date   = ?,
            return_date     = ?,
            status_id       = ?,
            repair_reason   = ?,
            to_be_returned  = ?,
            photo_path      = ?
        WHERE item_id = ?
    ";

    $bind_types = "siiissisisi"; // 11 parameters: s=6, i=5

    $bind_params = [
        $borrower_type,
        $stud_rec_id,
        $emp_rec_id,
        $mrep_id,
        $received_date,
        $return_date,
        $status_id,
        $repair_reason,
        $toBeReturned,
        $photo_path,
        $laptop_item_id
    ];

    $update_stmt = $conn->prepare($update_query);

    $update_stmt->bind_param($bind_types, ...$bind_params);

    if ($update_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Gadget distribution record updated successfully.";

        $logAction = "Updated distribution for gadget (Serial: $serial_no, Description: $laptop_desc_name) to " .
                     "Borrower Type: $borrower_type, " .
                     (isset($stud_rec_id) ? "Student ID: $receiver_id_input, " : "") .
                     (isset($emp_rec_id) ? "Employee ID: $receiver_id_input, " : "") .
                     "Accountable MREP ID: $mrep_id, " .
                     "Received Date: $received_date" .
                     ($photo_path ? ", Photo Path: $photo_path" : "");
        $logStmt = $conn->prepare("INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
        $logStmt->bind_param("is", $loggedInUserId, $logAction);
        $logStmt->execute();
        $logStmt->close();

        // 5. Handle the bag if the checkbox was ticked
        if ($has_bag && $bag_item_desc_id && $serial_no) {
            // Find the item ID of the bag with the same serial number and bag type
            $stmt_bag = $conn->prepare("SELECT b.bag_item_id, idesc.item_name 
                FROM bag_items b
                JOIN item_desc idesc ON b.item_desc_id = idesc.item_desc_id 
                WHERE b.serial_no = ? AND b.item_desc_id = ?");
            $stmt_bag->bind_param("si", $serial_no, $bag_item_desc_id);
            $stmt_bag->execute();
            $result_bag = $stmt_bag->get_result();

            if ($result_bag->num_rows === 1) {
                $row_bag = $result_bag->fetch_assoc();
                $bag_item_id = $row_bag['bag_item_id'];
                $bag_desc_name = $row_bag['item_name'];

                // Update bag_distribution instead of gadget_distribution
                $update_bag_query = "
                    UPDATE bag_distribution SET
                        borrower_type   = ?,
                        stud_rec_id     = ?,
                        receiver_id     = ?,
                        mrep_id         = ?,
                        received_date   = ?,
                        return_date     = ?,
                        status_id       = ?,
                        repair_reason   = ?,
                        photo_path      = ?
                    WHERE bag_item_id = ?
                ";

                error_log("SQL Query: " . $update_bag_query);
                error_log("Parameter count in query: " . substr_count($update_bag_query, '?'));
                
                $update_bag_stmt = $conn->prepare($update_bag_query);

                // Ensure all parameters are properly initialized
                $borrower_type = $borrower_type ?? null;
                $stud_rec_id = $stud_rec_id ?? null;
                $emp_rec_id = $emp_rec_id ?? null;
                $mrep_id = intval($mrep_id);
                $received_date = $received_date ?? null;
                $return_date = $return_date ?? null;
                $status_id = intval($status_id);
                $repair_reason = $repair_reason ?? null;
                $photo_path = $photo_path ?? null;
                $receipt_path = $receipt_path ?? null;
                $bag_item_id = intval($bag_item_id);

                error_log("Parameters to bind:");
                error_log("borrower_type: " . var_export($borrower_type, true));
                error_log("stud_rec_id: " . var_export($stud_rec_id, true));
                error_log("emp_rec_id: " . var_export($emp_rec_id, true));
                error_log("mrep_id: " . var_export($mrep_id, true));
                error_log("received_date: " . var_export($received_date, true));
                error_log("return_date: " . var_export($return_date, true));
                error_log("status_id: " . var_export($status_id, true));
                error_log("repair_reason: " . var_export($repair_reason, true));
                error_log("photo_path: " . var_export($photo_path, true));
                error_log("receipt_path: " . var_export($receipt_path, true));
                error_log("bag_item_id: " . var_export($bag_item_id, true));

                try {
                    $update_bag_stmt->bind_param(
                        "siiississi", // 10 parameters: s=5, i=5
                        $borrower_type,   // s (enum)
                        $stud_rec_id,     // i
                        $emp_rec_id,      // i
                        $mrep_id,         // i
                        $received_date,   // s (date)
                        $return_date,     // s (date)
                        $status_id,       // i
                        $repair_reason,   // s
                        $photo_path,      // s
                        $bag_item_id      // i
                    );


                    if ($update_bag_stmt->execute()) {
                        $response['message'] .= " Bag distribution record updated successfully.";
                        $logActionBag = "Updated distribution for bag (Serial: $serial_no, Description: $bag_desc_name) to " .
                                        "Borrower Type: $borrower_type, " .
                                        (isset($stud_rec_id) ? "Student ID: $receiver_id_input, " : "") .
                                        (isset($emp_rec_id) ? "Employee ID: $receiver_id_input, " : "") .
                                        "Accountable MREP ID: $mrep_id, " .
                                        "Received Date: $received_date" .
                                        ($photo_path ? ", Photo Path: $photo_path" : "");
                        $logStmtBag = $conn->prepare("INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
                        $logStmtBag->bind_param("is", $loggedInUserId, $logActionBag);
                        $logStmtBag->execute();
                        $logStmtBag->close();
                    } else {
                        $response['message'] .= " Error updating bag distribution record: " . $update_bag_stmt->error;
                        $response['success'] = false;
                    }
                } catch (Exception $e) {
                    error_log("Error in bind_param: " . $e->getMessage());
                    $response['message'] .= " Error binding parameters: " . $e->getMessage();
                    $response['success'] = false;
                }

                $update_bag_stmt->close();
            } else {
                $response['message'] .= " Bag with the same serial number and selected type not found in bag_items table.";
                $response['success'] = false;
            }
            $stmt_bag->close();
        }

    } else {
        $response['message'] = "Error updating gadget distribution record: " . $update_stmt->error;
        $response['success'] = false;
    }
    $update_stmt->close();

} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);

$conn->close();
?>