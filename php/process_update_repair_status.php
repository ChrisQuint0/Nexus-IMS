<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "nexus_ims_db_dummy");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if ($data && isset($data['dist_id']) && isset($data['status_id'])) {
    $dist_id = $conn->real_escape_string($data['dist_id']);
    $status_id = $conn->real_escape_string($data['status_id']);
    $staff = $_SESSION["username"];
    
    // Update gadget_distribution status
    $sql_update = "UPDATE gadget_distribution SET status_id = ? WHERE dist_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $status_id, $dist_id);

    if ($stmt_update->execute()) {
        // If the status is changed to "Claimed" (status_id = 3), log it to repair_logs
        if ($status_id == 3) {
            // Fetch necessary data from related tables using JOINs
            $sql_select = "SELECT
                                    i.box_no,
                                    e.emp_fname,
                                    e.emp_minit,
                                    e.emp_lname,
                                    d.department_name,
                                    CASE
                                        WHEN gd.borrower_type = 'student' THEN s.first_name
                                        WHEN gd.borrower_type = 'employee' THEN emp2.emp_fname
                                    END as receiver_fname,
                                    CASE
                                        WHEN gd.borrower_type = 'student' THEN s.middle_name
                                        WHEN gd.borrower_type = 'employee' THEN emp2.emp_minit
                                    END as receiver_minit,
                                    CASE
                                        WHEN gd.borrower_type = 'student' THEN s.last_name
                                        WHEN gd.borrower_type = 'employee' THEN emp2.emp_lname
                                    END as receiver_lname,
                                    s.section,
                                    id.item_name,
                                    i.serial_no,
                                    gd.received_date,
                                    gd.repair_reason
                                FROM gadget_distribution gd
                                LEFT JOIN items i ON gd.item_id = i.item_id
                                LEFT JOIN employees e ON gd.mrep_id = e.emp_rec_id
                                LEFT JOIN departments d ON e.department_id = d.department_id
                                LEFT JOIN students s ON gd.stud_rec_id = s.stud_rec_id
                                LEFT JOIN employees emp2 ON gd.receiver_id = emp2.emp_rec_id
                                LEFT JOIN item_desc id ON i.item_desc_id = id.item_desc_id
                                WHERE gd.dist_id = ?";

            $stmt_select = $conn->prepare($sql_select);
            $stmt_select->bind_param("i", $dist_id);
            $stmt_select->execute();
            $result = $stmt_select->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();

                // Construct names, handling null middle initials
                $accountable_name = $row['emp_fname'] . " ";
                if ($row['emp_minit']) {
                    $accountable_name .= $row['emp_minit'] . " ";
                }
                $accountable_name .= $row['emp_lname'];

                $receiver_name = $row['receiver_fname'] . " ";
                if ($row['receiver_minit']) {
                    $receiver_name .= $row['receiver_minit'] . " ";
                }
                $receiver_name .= $row['receiver_lname'];
                

                $box_number = $row['box_no'];
                $department = $row['department_name'];
                $item_name = $row['item_name'];
                $serial_number = $row['serial_no'];
                $repair_date = date("Y-m-d H:i:s"); // Include time
                $reason = $row['repair_reason'];
                $section = $row['section'];

                $sql_log = "INSERT INTO repair_logs (box_number, accountable, department, receiver, section, item_name, serial_number, repair_date, reason, staff)
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_log = $conn->prepare($sql_log);
                // Ensure correct data types and order
                $stmt_log->bind_param("isssssssss", 
                                        $box_number, 
                                        $accountable_name, 
                                        $department, 
                                        $receiver_name, 
                                        $section, 
                                        $item_name, 
                                        $serial_number, 
                                        $repair_date,  
                                        $reason, 
                                        $staff);
                $stmt_log->execute();
                $stmt_log->close();
            }
            $stmt_select->close();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update status: ' . $stmt_update->error]);
    }

    $stmt_update->close();
} else {
    echo json_encode(['error' => 'Invalid data received.']);
}

$conn->close();
?>
