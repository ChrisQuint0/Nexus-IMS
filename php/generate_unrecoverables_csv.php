<?php
include 'db_connection_header.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch lost and unrecoverable items for CSV
$sql = "SELECT
            s.status_name AS Status,
            i.box_no AS 'Box Number',
            CONCAT(e.emp_fname, ' ', e.emp_lname) AS Accountable,
            d.department_name AS Department,
            id.item_name AS 'Item Name',
            i.serial_no AS 'Serial No.'
        FROM gadget_distribution gd
        JOIN items i ON gd.item_id = i.item_id
        JOIN statuses s ON gd.status_id = s.status_id
        LEFT JOIN employees e ON gd.mrep_id = e.emp_rec_id
        LEFT JOIN departments d ON e.department_id = d.department_id
        JOIN item_desc id ON i.item_desc_id = id.item_desc_id
        WHERE s.status_name IN ('Lost', 'Unrecoverable')";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="unrecoverable_items.csv"');

    // Output CSV header
    $heading = $result->fetch_fields();
    $headerRow = array();
    foreach ($heading as $field) {
        $headerRow[] = $field->name;
    }
    fputcsv(fopen('php://output', 'w'), $headerRow);

    // Output CSV data
    while ($row = $result->fetch_assoc()) {
        fputcsv(fopen('php://output', 'a'), $row);
    }
} else {
    // If no data found, you might want to output a message
    echo "No lost or unrecoverable items found.";
}

$conn->close();
?>