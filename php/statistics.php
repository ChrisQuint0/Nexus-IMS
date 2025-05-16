<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

function get_statistics($conn, $filter = 'all')
{
    // Base query
    if ($filter !== 'all') {
        // Sanitize the filter value
        $safe_filter = $conn->real_escape_string($filter);

        // Build the statistics query with the exact item_name filter
        $query = "SELECT
            gd.status_id, COUNT(*) as count
            FROM
            gadget_distribution gd
            JOIN items i ON gd.item_id = i.item_id
            JOIN item_desc id ON i.item_desc_id = id.item_desc_id
            WHERE
            gd.status_id IN (1, 2, 3, 4, 5, 9, 11)
            AND id.item_name = '$safe_filter'
            GROUP BY gd.status_id";
    } else {
        // Query for 'all' filter - excludes laptop bags (item_desc_id = 3)
        $query = "SELECT
            gd.status_id, COUNT(*) as count
            FROM
            gadget_distribution gd
            JOIN items i ON gd.item_id = i.item_id
            JOIN item_desc id ON i.item_desc_id = id.item_desc_id
            WHERE
            gd.status_id IN (1, 2, 3, 4, 5, 9, 11)
            AND i.item_desc_id != 3
            GROUP BY gd.status_id";
    }

    $result = $conn->query($query);

    // Initialize counters with default values of 0
    $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 9 => 0, 11 => 0];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $counts[$row['status_id']] = $row['count'];
        }
        $result->free();
    }
    return $counts; // Return an associative array of counts by status ID
}

function get_dropdown_options($conn, $filter = 'all')
{
    $category_query = "SELECT DISTINCT item_name as category_value, item_name as display_name FROM item_desc WHERE item_desc_id != 3 ORDER BY display_name";
    $result = $conn->query($category_query);
    $options = '<option value="all"' . ($filter === 'all' ? ' selected' : '') . '>All</option>';
    if ($result) {
        while ($category = $result->fetch_assoc()) {
            $value = $category['category_value'];
            $display = $category['display_name'];
            $options .= '<option value="' . $value . '"' . ($filter === $value ? ' selected' : '') . '>' . $display . '</option>';
        }
        $result->free();
    } else {
        $options = '<option value="all">Error loading options</option>';
    }
    return $options;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';

        if ($action === 'loadInitialData') {
            $statusCounts = get_statistics($conn, $filter);
            $dropdownOptions = get_dropdown_options($conn, $filter);

            $response = [
                'available' => $statusCounts[1],
                'brand_new' => $statusCounts[2],
                'claimed' => $statusCounts[3],
                'for_repair' => $statusCounts[4],
                'defective' => $statusCounts[5],
                'unrecoverable' => $statusCounts[9], // Add unrecoverable count
                'lost' => $statusCounts[11], // Add lost count
                'statusCounts' => $statusCounts, // Send the counts for the charts
                'dropdownOptions' => $dropdownOptions,
                'filter' => $filter
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    }
}

$conn->close();
?>