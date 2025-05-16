<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "nexus_ims_db_dummy";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"));

if (
    isset($data->box_no) &&
    isset($data->item_desc_id) &&
    isset($data->serial_no) &&
    isset($data->accountable_id) &&
    isset($data->purchase_date) &&
    isset($_SESSION['user_id'])
) {
    $boxNo = $conn->real_escape_string($data->box_no);
    $itemDescId = $conn->real_escape_string($data->item_desc_id);
    $serialNo = $conn->real_escape_string($data->serial_no);
    $accountableId = $conn->real_escape_string($data->accountable_id);
    $purchaseDate = $conn->real_escape_string($data->purchase_date);
    $userId = $_SESSION['user_id'];

    // First, get the category_id for this item
    $sqlGetCategory = "SELECT category_id FROM item_desc WHERE item_desc_id = ?";
    $stmtGetCategory = $conn->prepare($sqlGetCategory);
    $stmtGetCategory->bind_param("i", $itemDescId);
    $stmtGetCategory->execute();
    $resultCategory = $stmtGetCategory->get_result();
    $categoryRow = $resultCategory->fetch_assoc();
    $categoryId = $categoryRow['category_id'];
    $stmtGetCategory->close();

    // Retrieve the name of the accountable employee
    $sqlGetAccountableName = "SELECT emp_fname, emp_lname FROM employees WHERE emp_rec_id = ?";
    $stmtGetAccountableName = $conn->prepare($sqlGetAccountableName);
    $stmtGetAccountableName->bind_param("i", $accountableId);
    $stmtGetAccountableName->execute();
    $resultAccountableName = $stmtGetAccountableName->get_result();

    if ($resultAccountableName->num_rows === 1) {
        $rowAccountableName = $resultAccountableName->fetch_assoc();
        $accountableName = $rowAccountableName['emp_fname'] . ' ' . $rowAccountableName['emp_lname'];

        if ($categoryId == 3) { // If it's a bag
            // Insert into bag_items table
            $sqlBagItems = "INSERT INTO bag_items (box_no, item_desc_id, serial_no) VALUES (?, ?, ?)";
            $stmtBagItems = $conn->prepare($sqlBagItems);
            $stmtBagItems->bind_param("iis", $boxNo, $itemDescId, $serialNo);

            if ($stmtBagItems->execute()) {
                $bagItemId = $conn->insert_id;

                // Insert into bag_distribution table with NULL borrower_type and receiver_id
                $sqlBagDistribution = "INSERT INTO bag_distribution (bag_item_id, borrower_type, receiver_id, mrep_id, status_id) VALUES (?, NULL, NULL, ?, 2)";
                $stmtBagDistribution = $conn->prepare($sqlBagDistribution);
                $stmtBagDistribution->bind_param("ii", $bagItemId, $accountableId);

                if ($stmtBagDistribution->execute()) {
                    // Log action in audit_log
                    $action = "Added new bag with box_no: $boxNo, serial_no: $serialNo, accountable to: $accountableName";
                    $sqlAudit = "INSERT INTO audit_log (user_id, action) VALUES (?, ?)";
                    $stmtAudit = $conn->prepare($sqlAudit);
                    $stmtAudit->bind_param("is", $userId, $action);
                    $stmtAudit->execute();
                    $stmtAudit->close();

                    echo json_encode(["success" => true]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "error" => "Error adding to bag_distribution: " . $stmtBagDistribution->error
                    ]);
                }

                $stmtBagDistribution->close();
            } else {
                echo json_encode([
                    "success" => false,
                    "error" => "Error adding to bag_items: " . $stmtBagItems->error
                ]);
            }

            $stmtBagItems->close();
        } else {
            // Insert into items table for non-bag items
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
                    // Log action in audit_log
                    $action = "Added new item with box_no: $boxNo, serial_no: $serialNo, purchase_date: $purchaseDate, assigned to: $accountableName";
                    $sqlAudit = "INSERT INTO audit_log (user_id, action) VALUES (?, ?)";
                    $stmtAudit = $conn->prepare($sqlAudit);
                    $stmtAudit->bind_param("is", $userId, $action);
                    $stmtAudit->execute();
                    $stmtAudit->close();

                    echo json_encode(["success" => true]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "error" => "Error adding to gadget_distribution: " . $stmtDistribution->error
                    ]);
                }

                $stmtDistribution->close();
            } else {
                echo json_encode([
                    "success" => false,
                    "error" => "Error adding to items: " . $stmtItems->error
                ]);
            }

            $stmtItems->close();
        }
    } else {
        $error = "Missing required fields.";
        if (!isset($_SESSION['user_id'])) {
            $error .= " User session not found.";
        }
        echo json_encode(["success" => false, "error" => $error]);
    }

    $stmtGetAccountableName->close();
    $conn->close();
}
?>
