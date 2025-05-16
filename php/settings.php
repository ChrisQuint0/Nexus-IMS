<?php
include 'db.php';

// Fetch items
$itemOptions = '';
$item = $conn->query("SELECT * FROM item_list");
while ($row = $item->fetch_assoc()) {
  $itemOptions .= "<option value='{$row['item_list_id']}'>{$row['item_desc']}</option>";
}

// Fetch sections
$sectionOptions = '';
$section = $conn->query("SELECT * FROM section_list");
while ($row = $section->fetch_assoc()) {
  $sectionOptions .= "<option value='{$row['section_list_id']}'>{$row['section']}</option>";
}

// Fetch employees
$employeeOptions = '';
$employees = $conn->query("SELECT * FROM employee_list");
while ($row = $employees->fetch_assoc()) {
  $employeeOptions .= "<option value='{$row['employee_list_id']}'>{$row['employee_name']}</option>";
}

// Load HTML
$html = file_get_contents('/pages/settings.html');

$html = str_replace('{{items}}', $itemOptions, $html);
$html = str_replace('{{sections}}', $sectionOptions, $html);
$html = str_replace('{{employees}}', $employeeOptions, $html);

echo $html;
