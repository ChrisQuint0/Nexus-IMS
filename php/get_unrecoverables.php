<?php
include 'db_connection_header.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch unrecoverable items
$sql = "SELECT
            gd.dist_id,
            s.status_name,
            i.box_no,
            CONCAT(e.emp_fname, ' ', e.emp_lname) AS accountable,
            d.department_name,
            id.item_name,
            i.serial_no,
            gd.status_id
        FROM gadget_distribution gd
        JOIN items i ON gd.item_id = i.item_id
        JOIN statuses s ON gd.status_id = s.status_id
        LEFT JOIN employees e ON gd.mrep_id = e.emp_rec_id
        LEFT JOIN departments d ON e.department_id = d.department_id
        JOIN item_desc id ON i.item_desc_id = id.item_desc_id
        WHERE s.status_name IN ('Lost', 'Unrecoverable')";

$result = $conn->query($sql);

$data = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Fetch all statuses for the dropdown
$statuses_sql = "SELECT status_id, status_name FROM statuses";
$statuses_result = $conn->query($statuses_sql);
$statuses = array();
if ($statuses_result->num_rows > 0) {
    while ($row = $statuses_result->fetch_assoc()) {
        $statuses[$row['status_id']] = $row['status_name'];
    }
}

$response = array('data' => $data, 'statuses' => $statuses);

// Set content type to JSON
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>