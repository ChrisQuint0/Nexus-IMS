<?php
include 'db_connection_header.php';

session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "User session not found."]);
    $conn->close();
    exit();
}

if ($data && is_array($data)) {
    $uploadSuccess = true;
    $errorMessages = [];
    $uploadedCount = 0;
    $userId = $_SESSION['user_id'];

    foreach ($data as $item) {
        $boxNo = isset($item['box_no']) ? $conn->real_escape_string($item['box_no']) : null;
        $itemDescId = isset($item['item_desc_id']) ? intval($item['item_desc_id']) : null;
        $serialNo = isset($item['serial_no']) ? $conn->real_escape_string($item['serial_no']) : null;
        $accountableId = isset($item['accountable_id']) ? intval($item['accountable_id']) : null;
        $purchaseDate = isset($item['purchase_date']) ? $conn->real_escape_string($item['purchase_date']) : null;

        if ($boxNo === null || $itemDescId === null || $serialNo === null || $accountableId === null) {
            $uploadSuccess = false;
            $errorMessages[] = "Missing required fields for an item.";
            continue;
        }

        // Retrieve the name of the accountable employee
        $sqlGetAccountableName = "SELECT emp_fname, emp_lname FROM employees WHERE emp_rec_id = ?";
        $stmtGetAccountableName = $conn->prepare($sqlGetAccountableName);
        $stmtGetAccountableName->bind_param("i", $accountableId);
        $stmtGetAccountableName->execute();
        $resultAccountableName = $stmtGetAccountableName->get_result();

        if ($resultAccountableName->num_rows !== 1) {
            $uploadSuccess = false;
            $errorMessages[] = "Invalid accountable ID provided for an item.";
            $stmtGetAccountableName->close();
            continue;
        }
        $rowAccountableName = $resultAccountableName->fetch_assoc();
        $accountableName = $rowAccountableName['emp_fname'] . ' ' . $rowAccountableName['emp_lname'];
        $stmtGetAccountableName->close();

        // Insert into items table, including purchase_date
        $sqlItems = "INSERT INTO items (box_no, item_desc_id, serial_no, purchase_date) VALUES (?, ?, ?, ?)";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bind_param("siss", $boxNo, $itemDescId, $serialNo, $purchaseDate);

        if ($stmtItems->execute()) {
            $itemId = $conn->insert_id;

            // Insert into gadget_distribution table
            $sqlDistribution = "INSERT INTO gadget_distribution (item_id, mrep_id, status_id, to_be_returned) VALUES (?, ?, 2, 0)";
            $stmtDistribution = $conn->prepare($sqlDistribution);
            $stmtDistribution->bind_param("ii", $itemId, $accountableId);

            if ($stmtDistribution->execute()) {
                // Log the insertion in the audit_log table with the accountable name and purchase date
                $action = "Added item via CSV - box_no: $boxNo, serial_no: $serialNo, purchase_date: $purchaseDate, assigned to: " . $accountableName;
                $sqlAudit = "INSERT INTO audit_log (user_id, action) VALUES (?, ?)";
                $stmtAudit = $conn->prepare($sqlAudit);
                $stmtAudit->bind_param("is", $userId, $action);
                $stmtAudit->execute();
                $stmtAudit->close();
                $uploadedCount++;
            } else {
                $uploadSuccess = false;
                $errorMessages[] = "Error adding to gadget_distribution for item with serial: " . $serialNo . " - " . $stmtDistribution->error;
            }
            $stmtDistribution->close();
        } else {
            $uploadSuccess = false;
            $errorMessages[] = "Error adding to items for item with serial: " . $serialNo . " - " . $stmtItems->error;
        }
        $stmtItems->close();
    }

    if ($uploadSuccess) {
        echo json_encode(["success" => true, "message" => "$uploadedCount items uploaded successfully."]);
    } else {
        echo json_encode(["success" => false, "error" => "Some items failed to upload. Details: " . implode(", ", $errorMessages)]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Invalid data received."]);
}

$conn->close();
?>