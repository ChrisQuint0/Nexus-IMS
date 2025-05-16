<?php
include 'db.php';

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if 'type' is set in the POST data
    if (isset($_POST['type'])) {
        // Get the 'type' value
        $type = $_POST['type'];

        // Initialize variables
        $table = '';
        $id_field = '';
        $id = 0;

        // Item deletion
        if ($type === 'item' && isset($_POST['item_list_id'])) {
            $table = 'item_list';
            $id_field = 'item_list_id';
            $id = intval($_POST['item_list_id']);
        }
        // Section deletion
        elseif ($type === 'section' && isset($_POST['section_list_id'])) {
            $table = 'section_list';
            $id_field = 'section_list_id';
            $id = intval($_POST['section_list_id']);
        }
        // Employee deletion
        elseif ($type === 'employee' && isset($_POST['employee_list_id'])) {
            $table = 'employee_list';
            $id_field = 'employee_list_id';
            $id = intval($_POST['employee_list_id']);
        }

        // Perform deletion if ID and table are valid
        if ($table && $id > 0) {
            $stmt = $conn->prepare("DELETE FROM $table WHERE $id_field = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

header("Location: settings.php");
exit;
?>
