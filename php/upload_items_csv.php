<?php
include 'db_connection_header.php';

session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "error" => "⚠️ Authentication Required: Please log in to continue."]);
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
        $categoryId = isset($item['category_id']) ? intval($item['category_id']) : null;

        if ($boxNo === null || $itemDescId === null || $accountableId === null) {
            $uploadSuccess = false;
            $errorMessages[] = "⚠️ Missing Required Fields: One or more items are missing required information. Please check all mandatory fields.";
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
            $errorMessages[] = "⚠️ Invalid Accountable ID: The specified accountable person does not exist in the system for box number: $boxNo";
            $stmtGetAccountableName->close();
            continue;
        }
        $rowAccountableName = $resultAccountableName->fetch_assoc();
        $accountableName = $rowAccountableName['emp_fname'] . ' ' . $rowAccountableName['emp_lname'];
        $stmtGetAccountableName->close();

        if ($categoryId === 3) {
            // Insert into bag_items table
            $sqlBagItems = "INSERT INTO bag_items (box_no, item_desc_id, serial_no, purchase_date) VALUES (?, ?, ?, ?)";
            $stmtBagItems = $conn->prepare($sqlBagItems);
            $stmtBagItems->bind_param("siss", $boxNo, $itemDescId, $serialNo, $purchaseDate);

            if ($stmtBagItems->execute()) {
                $bagItemId = $conn->insert_id;

                // Insert into bag_distribution table
                $sqlBagDistribution = "INSERT INTO bag_distribution (bag_item_id, mrep_id, status_id) VALUES (?, ?, 2)";
                $stmtBagDistribution = $conn->prepare($sqlBagDistribution);
                $stmtBagDistribution->bind_param("ii", $bagItemId, $accountableId);

                if ($stmtBagDistribution->execute()) {
                    $uploadedCount++;

                    // Log the bag item addition
                    $logAction = "Added bag item via CSV import - " .
                                "Box No: " . ($boxNo ?? 'N/A') . ", " .
                                "Item Description ID: " . ($itemDescId ?? 'N/A') . ", " .
                                "Serial No: " . ($serialNo ?? 'N/A') . ", " .
                                "Purchase Date: " . ($purchaseDate ?? 'N/A') . ", " .
                                "Accountable Person: $accountableName";

                    $logSql = "INSERT INTO audit_log (user_id, action) VALUES (?, ?)";
                    $logStmt = $conn->prepare($logSql);
                    $logStmt->bind_param("is", $userId, $logAction);
                    $logStmt->execute();
                    $logStmt->close();
                } else {
                    $uploadSuccess = false;
                    $errorMessages[] = "⚠️ Database Error: Failed to insert bag distribution record for box number: $boxNo. Please try again.";
                }
                $stmtBagDistribution->close();
            } else {
                $uploadSuccess = false;
                $errorMessages[] = "⚠️ Database Error: Failed to insert bag item record for box number: $boxNo. Please try again.";
            }
            $stmtBagItems->close();
        } else {
            // Insert into items table
            $sqlItems = "INSERT INTO items (box_no, item_desc_id, serial_no, purchase_date) VALUES (?, ?, ?, ?)";
            $stmtItems = $conn->prepare($sqlItems);
            $stmtItems->bind_param("siss", $boxNo, $itemDescId, $serialNo, $purchaseDate);

            if ($stmtItems->execute()) {
                $itemId = $conn->insert_id;

                // Insert into gadget_distribution table
                $sqlDistribution = "INSERT INTO gadget_distribution (item_id, mrep_id, status_id) VALUES (?, ?, 2)";
                $stmtDistribution = $conn->prepare($sqlDistribution);
                $stmtDistribution->bind_param("ii", $itemId, $accountableId);

                if ($stmtDistribution->execute()) {
                    $uploadedCount++;

                    // Log the item addition
                    $logAction = "Added item via CSV import - " .
                                "Box No: " . ($boxNo ?? 'N/A') . ", " .
                                "Item Description ID: " . ($itemDescId ?? 'N/A') . ", " .
                                "Serial No: " . ($serialNo ?? 'N/A') . ", " .
                                "Purchase Date: " . ($purchaseDate ?? 'N/A') . ", " .
                                "Accountable Person: $accountableName";

                    $logSql = "INSERT INTO audit_log (user_id, action) VALUES (?, ?)";
                    $logStmt = $conn->prepare($logSql);
                    $logStmt->bind_param("is", $userId, $logAction);
                    $logStmt->execute();
                    $logStmt->close();
                } else {
                    $uploadSuccess = false;
                    $errorMessages[] = "⚠️ Database Error: Failed to insert distribution record for box number: $boxNo. Please try again.";
                }
                $stmtDistribution->close();
            } else {
                $uploadSuccess = false;
                $errorMessages[] = "⚠️ Database Error: Failed to insert item record for box number: $boxNo. Please try again.";
            }
            $stmtItems->close();
        }
    }

    if ($uploadSuccess) {
        echo json_encode(["success" => true, "message" => "✅ Upload Complete: Successfully added $uploadedCount items to the inventory."]);
    } else {
        echo json_encode(["success" => false, "error" => "⚠️ Upload Failed: Some items could not be added to the inventory. Details:\n" . implode("\n", $errorMessages)]);
    }
} else {
    echo json_encode(["success" => false, "error" => "⚠️ Invalid Data Format: The uploaded data is not in the expected format. Please ensure you are using the correct CSV template."]);
}

$conn->close();
?>