<?php
require_once 'db_functions.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Get database connection
$conn = get_database_connection();

$data = json_decode(file_get_contents("php://input"), true);

if ($data && isset($data['dist_id']) && isset($data['item_type'])) {
    $dist_id = $conn->real_escape_string($data['dist_id']);
    $item_type = $conn->real_escape_string($data['item_type']);
    $return_date = date("Y-m-d");
    $item_condition = isset($data['item_condition']) ? $conn->real_escape_string($data['item_condition']) : null;
    $remarks = isset($data['remarks']) ? $conn->real_escape_string($data['remarks']) : null;
    $staff = $_SESSION["username"];

    $conn->begin_transaction();

    try {
        if ($item_type === 'bag') {
            // Process Bag Return
            $sql_select_bag = "SELECT bd.*, bi.*
                                 FROM bag_distribution bd
                                 JOIN bag_items bi ON bd.bag_item_id = bi.bag_item_id
                                 WHERE bd.bag_dist_id = ?";
            $stmt_select_bag = $conn->prepare($sql_select_bag);
            $stmt_select_bag->bind_param("i", $dist_id);
            $stmt_select_bag->execute();
            $result_select_bag = $stmt_select_bag->get_result();
            $current_bag_record = $result_select_bag->fetch_assoc();
            $stmt_select_bag->close();

            if ($current_bag_record) {
                // Insert into archive_bag_distribution
                $sql_archive_bag = "INSERT INTO archive_bag_distribution (archive_bag_dist_id, borrower_type, stud_rec_id, receiver_id, bag_item_id, mrep_id, received_date, return_date, status_id, repair_reason, photo_path)
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_archive_bag = $conn->prepare($sql_archive_bag);
                $stmt_archive_bag->bind_param("sssssssssss",
                    $current_bag_record['bag_dist_id'],
                    $current_bag_record['borrower_type'],
                    $current_bag_record['stud_rec_id'],
                    $current_bag_record['receiver_id'],
                    $current_bag_record['bag_item_id'],
                    $current_bag_record['mrep_id'],
                    $current_bag_record['received_date'],
                    $return_date,
                    $current_bag_record['status_id'],
                    $current_bag_record['repair_reason'],
                    $current_bag_record['photo_path']
                );
                $stmt_archive_bag->execute();

                if ($stmt_archive_bag->affected_rows > 0) {
                    $archive_bag_record_id = $conn->insert_id;
                    $stmt_archive_bag->close();

                    // Insert into return_bag_logs
                    $sql_log_bag = "INSERT INTO return_bag_logs (bag_dist_record_id, returned_at, item_condition, remarks, staff)
                                                 VALUES (?, NOW(), ?, ?, ?)";
                    $stmt_log_bag = $conn->prepare($sql_log_bag);
                    $stmt_log_bag->bind_param("isss", $archive_bag_record_id, $item_condition, $remarks, $staff);
                    $stmt_log_bag->execute();
                    $stmt_log_bag->close();

                    // Nullify fields in bag_distribution
                    $new_bag_status_id = 1; // Default to Available
                    $sql_update_bag = "UPDATE bag_distribution
                                         SET return_date = ?,
                                             borrower_type = NULL,
                                             stud_rec_id = NULL,
                                             receiver_id = NULL,
                                             received_date = NULL,
                                             status_id = ?,
                                             photo_path = NULL,
                                             repair_reason = NULL
                                         WHERE bag_dist_id = ?";
                    $stmt_update_bag = $conn->prepare($sql_update_bag);
                    $stmt_update_bag->bind_param("sii", $return_date, $new_bag_status_id, $dist_id);
                    $stmt_update_bag->execute();
                    $stmt_update_bag->close();

                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Bag returned successfully']);
                } else {
                    $conn->rollback();
                    echo json_encode(['error' => 'Failed to archive bag distribution record.']);
                }
            } else {
                $conn->rollback();
                echo json_encode(['error' => 'Bag distribution record not found.']);
            }
        } else if ($item_type === 'gadget') {
            // Process Gadget Return
            $sql_select = "SELECT * FROM gadget_distribution WHERE dist_id = ?";
            $stmt_select = $conn->prepare($sql_select);
            $stmt_select->bind_param("i", $dist_id);
            $stmt_select->execute();
            $current_record = $stmt_select->get_result()->fetch_assoc();
            $stmt_select->close();

            if ($current_record) {
                // Insert into archive_distribution
                $sql_archive = "INSERT INTO archive_distribution (archive_dist_id, borrower_type, stud_rec_id, receiver_id, item_id, mrep_id, received_date, return_date, status_id, photo_path)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_archive = $conn->prepare($sql_archive);
                $stmt_archive->bind_param("ssssssssss",
                    $current_record['dist_id'],
                    $current_record['borrower_type'],
                    $current_record['stud_rec_id'],
                    $current_record['receiver_id'],
                    $current_record['item_id'],
                    $current_record['mrep_id'],
                    $current_record['received_date'],
                    $return_date,
                    $current_record['status_id'],
                    $current_record['photo_path']
                );
                $stmt_archive->execute();
                $archive_record_id = $conn->insert_id;
                $stmt_archive->close();

                // Insert into return_logs
                $sql_log = "INSERT INTO return_logs (dist_id, returned_at, item_condition, remarks, staff)
                             VALUES (?, NOW(), ?, ?, ?)";
                $stmt_log = $conn->prepare($sql_log);
                $stmt_log->bind_param("isss", $archive_record_id, $item_condition, $remarks, $staff);
                $stmt_log->execute();
                $stmt_log->close();

                // Update gadget_distribution
                $new_status_id = 1; // Default to Available
                if ($item_condition === "Defective") {
                    $new_status_id = 5; // Set to Defective
                } elseif ($item_condition === "Lost") {
                    $new_status_id = 11; // Set to Lost
                } elseif ($item_condition === "Unrecoverable") {
                    $new_status_id = 9; // Set to Unrecoverable
                }

                $sql_update = "UPDATE gadget_distribution
                                     SET return_date = ?,
                                         borrower_type = NULL,
                                         stud_rec_id = NULL,
                                         receiver_id = NULL,
                                         received_date = NULL,
                                         status_id = ?,
                                         to_be_returned = 0,
                                         photo_path = NULL
                                     WHERE dist_id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("sii", $return_date, $new_status_id, $dist_id);
                $stmt_update->execute();
                $stmt_update->close();

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Gadget returned successfully']);
            } else {
                $conn->rollback();
                echo json_encode(['error' => 'Gadget distribution record not found.']);
            }
        } else {
            echo json_encode(['error' => 'Invalid item type.']);
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['error' => 'Invalid data received.']);
}

$conn->close();
?>