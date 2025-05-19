<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_functions.php';

// Get database connection
$conn = get_pdo_connection();

$conn = get_database_connection();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(array("message" => "Database connection failed: " . $conn->connect_error));
    exit;
}

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if ($data === null) {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid JSON data."));
    exit;
}

foreach ($data as $record) {
    $borrowerType = $record['borrowerType'];
    $studentId = $record['studentId'];
    $receiverId = $record['receiverId'];
    $itemName = $record['itemName'];
    $serialNumber = $record['serialNumber'];
    $receivedDate = $record['receivedDate'];
    $categoryId = $record['categoryId'];
    $studRecId = NULL;

    // Handle borrower type and set appropriate IDs
    if ($borrowerType == 'student' && $studentId != NULL) {
        $sql = "SELECT stud_rec_id FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $studRecId = $row['stud_rec_id'];
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Student ID '$studentId' not found in the students table."));
            $stmt->close();
            $conn->close();
            exit;
        }
        $stmt->close();
        $receiverId = NULL;
    } elseif ($borrowerType == 'employee') {
        $studentId = NULL;
        $studRecId = NULL;
    } else {
        $studentId = NULL;
        $studRecId = NULL;
    }

    if ($categoryId == 3) { // It's a bag
        // Lookup bag_item_id from bag_items table
        $sql = "SELECT bag_item_id FROM bag_items WHERE serial_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serialNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $bagItemId = $row['bag_item_id'];
            $stmt->close();

            // Update bag_distribution
            $sqlUpdateBagDist = "UPDATE bag_distribution SET borrower_type = ?, stud_rec_id = ?, receiver_id = ?, received_date = ?, status_id = 3 WHERE bag_item_id = ?";
            $stmtUpdateBagDist = $conn->prepare($sqlUpdateBagDist);
            $stmtUpdateBagDist->bind_param("ssisi", $borrowerType, $studRecId, $receiverId, $receivedDate, $bagItemId);

            if ($stmtUpdateBagDist->execute()) {
                // Success for bag distribution update
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Error updating bag_distribution: " . $stmtUpdateBagDist->error));
                $stmtUpdateBagDist->close();
                $conn->close();
                exit;
            }
            $stmtUpdateBagDist->close();
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Serial Number '$serialNumber' not found in bag_items table."));
            $stmt->close();
            $conn->close();
            exit;
        }
    } else { // It's not a bag, update gadget_distribution
        // Lookup item_id from items table
        $sql = "SELECT item_id FROM items WHERE serial_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $serialNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $itemId = $row['item_id'];
            $stmt->close();

            // Update gadget_distribution
            $sqlUpdateGadgetDist = "UPDATE gadget_distribution SET borrower_type = ?, stud_rec_id = ?, receiver_id = ?, received_date = ?, status_id = 3 WHERE item_id = ?";
            $stmtUpdateGadgetDist = $conn->prepare($sqlUpdateGadgetDist);
            $stmtUpdateGadgetDist->bind_param("ssisi", $borrowerType, $studRecId, $receiverId, $receivedDate, $itemId);

            if ($stmtUpdateGadgetDist->execute()) {
                // Success for gadget distribution update
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Error updating gadget_distribution: " . $stmtUpdateGadgetDist->error));
                $stmtUpdateGadgetDist->close();
                $conn->close();
                exit;
            }
            $stmtUpdateGadgetDist->close();
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Serial Number '$serialNumber' not found in items table."));
            $stmt->close();
            $conn->close();
            exit;
        }
    }
}

http_response_code(200);
echo json_encode(array("message" => "All records processed successfully."));

$conn->close();
?>