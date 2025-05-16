<?php

include 'db_connection_header.php';

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => "Database connection failed: " . $conn->connect_error]));
}

$response = ['success' => false, 'message' => ''];

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['dist_id']) || !isset($data['status_id'])) {
    http_response_code(400);
    $response['message'] = 'Missing required data: dist_id and status_id.';
} else {
    $dist_id = filter_var($data['dist_id'], FILTER_SANITIZE_NUMBER_INT);
    $status_id = filter_var($data['status_id'], FILTER_SANITIZE_NUMBER_INT);

    if ($dist_id === false || $status_id === false) {
        http_response_code(400);
        $response['message'] = 'Invalid data format for dist_id or status_id.';
    } else {
        $getItemIdSql = "SELECT item_id FROM gadget_distribution WHERE dist_id = ?";
        $stmtItemId = $conn->prepare($getItemIdSql);

        if ($stmtItemId) {
            $stmtItemId->bind_param("i", $dist_id);
            $stmtItemId->execute();
            $resultItemId = $stmtItemId->get_result();

            if ($resultItemId->num_rows > 0) {
                $rowItemId = $resultItemId->fetch_assoc();
                $item_id = $rowItemId['item_id'];
                $stmtItemId->close();

                $updateDistSql = "UPDATE gadget_distribution SET status_id = ? WHERE dist_id = ?";
                $stmtDistUpdate = $conn->prepare($updateDistSql);

                if ($stmtDistUpdate) {
                    $stmtDistUpdate->bind_param("ii", $status_id, $dist_id);

                    if ($stmtDistUpdate->execute()) {
                        http_response_code(200);
                        $response['success'] = true;
                        // Optionally fetch and return updated data
                    } else {
                        http_response_code(500);
                        $response['message'] = "Error updating gadget distribution: " . $stmtDistUpdate->error;
                        error_log("Database Error: " . $stmtDistUpdate->error . " - File: " . __FILE__ . ", Line: " . __LINE__);
                        $response['message'] = "An unexpected error occurred during update.";
                    }
                    $stmtDistUpdate->close();
                } else {
                    http_response_code(500);
                    $response['message'] = "Error preparing update statement: " . $conn->error;
                    error_log("Database Error: " . $conn->error . " - File: " . __FILE__ . ", Line: " . __LINE__);
                    $response['message'] = "An unexpected error occurred.";
                }
            } else {
                http_response_code(404);
                $response['message'] = "Could not find item_id for dist_id: " . $dist_id;
            }
        } else {
            http_response_code(500);
            $response['message'] = "Error preparing item_id query: " . $conn->error;
            error_log("Database Error: " . $conn->error . " - File: " . __FILE__ . ", Line: " . __LINE__);
            $response['message'] = "An unexpected error occurred.";
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();

?>