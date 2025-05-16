<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nexus_ims_db_dummy";

$conn = new mysqli($servername, $username, $password, $dbname);
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
    $serialNumber = $record['serialNumber'];
    $receivedDate = $record['receivedDate'];
    $studRecId = NULL; // Initialize studRecId

    $sql = "SELECT item_id FROM items WHERE serial_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $serialNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        http_response_code(400);
        echo json_encode(array("message" => "Serial Number '$serialNumber' not found in the database."));
        exit;
    }

    $row = $result->fetch_assoc();
    $itemId = $row['item_id'];
    $stmt->close();

    // Handle borrowerType and set appropriate IDs
    if ($borrowerType == 'student' && $studentId != NULL) {
        // Lookup stud_rec_id from students table
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
        $receiverId = NULL; // Ensure receiverId is NULL for students
    } elseif ($borrowerType == 'employee') {
        $studentId = NULL; // Ensure studentId is NULL for employees
        $studRecId = NULL;
    } else {
        $studentId = NULL;
        $studRecId = NULL;
    }


    $sql = "UPDATE gadget_distribution
            SET borrower_type = ?,
                stud_rec_id = ?,
                receiver_id = ?,
                received_date = ?,
                status_id = 3
            WHERE item_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(array("message" => "Error preparing statement: " . $conn->error));
        $conn->close();
        exit;
    }
    $stmt->bind_param("ssisi", $borrowerType, $studRecId, $receiverId, $receivedDate, $itemId);

    if ($stmt->execute()) {
        // success
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Error updating gadget_distribution: " . $stmt->error . " SQL: " . $sql));
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();
}

http_response_code(200);
echo json_encode(array("message" => "All records updated successfully."));

$conn->close();
?>
