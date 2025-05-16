<?php
// Set error reporting to catch potential issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get parameters from POST request
$userType = isset($_POST['userType']) ? $_POST['userType'] : '';
$department = isset($_POST['department']) ? $_POST['department'] : '';
$onlyDepartment = isset($_POST['onlyDepartment']) ? $_POST['onlyDepartment'] : '';

// Get selected columns, defaulting to all columns if none selected
$selectedColumns = isset($_POST['columns']) ? json_decode($_POST['columns'], true) : [];

// Default to these columns if none selected
if (empty($selectedColumns)) {
    $selectedColumns = ['borrower_name', 'serial_no', 'item_name', 'MR'];
}

// Include database connection
include 'db.php';

// Define column mapping between ID and display name
$columnMapping = [
    'dist_id' => 'Dist ID',
    'borrower_type' => 'Borrower Type',
    'item_id' => 'Item ID',
    'serial_no' => 'Serial No',
    'item_name' => 'Item Name',
    'received_date' => 'Received Date',
    'returned_date' => 'Return Date',
    'borrower_name' => 'Borrower Name',
    'section' => 'Section',
    'MR' => 'MR',
    'department' => 'Department'
];

// Define SQL column mapping (database field names)
$sqlColumns = [
    'dist_id' => 'gd.dist_id',
    'borrower_type' => 'gd.borrower_type',
    'item_id' => 'gd.item_id',
    'serial_no' => 'i.serial_no',
    'item_name' => 'idesc.item_name',
    'received_date' => 'gd.received_date',
    'returned_date' => 'gd.return_date',
    'borrower_name' => "CASE
                WHEN gd.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                WHEN gd.borrower_type IS NOT NULL THEN CONCAT(e.emp_fname, ' ', e.emp_lname)
                ELSE 'Unknown'
            END",
    'section' => 's.section',
    'MR' => "CONCAT(emp.emp_fname, ' ', emp.emp_lname)",
    'department' => 'd.department_name'
];

// Build the SQL SELECT part with only selected columns
$selectColumns = [];
foreach ($selectedColumns as $columnId) {
    if (isset($sqlColumns[$columnId])) {
        $selectColumns[] = $sqlColumns[$columnId] . " AS " . $columnId;
    }
}
$selectClause = implode(", ", $selectColumns);

// Base query with joins - Note the status_id = 1 for available items (instead of 3 for claimed)
$baseQuery = "FROM
    gadget_distribution gd
LEFT JOIN students s ON gd.stud_rec_id = s.stud_rec_id
LEFT JOIN employees e ON gd.receiver_id = e.emp_rec_id
LEFT JOIN items i ON gd.item_id = i.item_id
LEFT JOIN item_desc idesc ON i.item_desc_id = idesc.item_desc_id
LEFT JOIN employees emp ON gd.mrep_id = emp.emp_rec_id
LEFT JOIN departments d ON emp.department_id = d.department_id
WHERE
    gd.status_id = 1";

try {
    // Prepare query based on user type
    if ($userType != 'admin') {
        // Build query for department manager view
        $sql = "SELECT " . $selectClause . " " . $baseQuery . " AND emp.department_id = ?";
        $debug_department = intval($department);

        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("i", $debug_department);
    } else if ($userType == 'admin' && $onlyDepartment != '') {
        // Build query for admin view with specific department
        $sql = "SELECT " . $selectClause . " " . $baseQuery . " AND emp.department_id = ?";
        $debug_department = intval($onlyDepartment);

        // Prepare and execute the statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("i", $debug_department);
    } else {
        // For admin without department filter, get all departments
        $sql = "SELECT " . $selectClause . " " . $baseQuery;
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
    }

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    // Get department name for the report title
    $departmentName = 'All Departments';
    if ($userType != 'admin' || ($userType == 'admin' && $onlyDepartment != '')) {
        $deptId = ($userType == 'admin') ? intval($onlyDepartment) : intval($department);
        $deptQuery = "SELECT department_name FROM departments WHERE department_id = ?";
        $deptStmt = $conn->prepare($deptQuery);
        $deptStmt->bind_param("i", $deptId);
        $deptStmt->execute();
        $deptResult = $deptStmt->get_result();
        if ($deptRow = $deptResult->fetch_assoc()) {
            $departmentName = $deptRow['department_name'];
        }
        $deptStmt->close();
    }

    // Load TCPDF library
    require_once('../TCPDF-main/tcpdf.php');

    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Inventory System');
    $pdf->SetAuthor('System Administrator');
    $pdf->SetTitle('Available Items Report');
    $pdf->SetSubject('Inventory of Available Items');
    $pdf->SetKeywords('Inventory, Available Items, Report');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', 'B', 16);

    // Title
    $title = "Available Items Inventory Report";
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, "Department: " . $departmentName, 0, 1, 'C');
    $pdf->Cell(0, 8, "Date Generated: " . date('F d, Y'), 0, 1, 'C');
    $pdf->Ln(5);

    // Calculate column widths based on selected columns
    $numColumns = count($selectedColumns);
    $pageWidth = $pdf->GetPageWidth() - 30; // 30mm margins total (15mm each side)
    $colWidths = array();

    // Assign different widths based on column type
    foreach ($selectedColumns as $columnId) {
        switch ($columnId) {
            case 'borrower_name':
                $colWidths[$columnId] = $pageWidth * 0.18;
                break;
            case 'item_name':
                $colWidths[$columnId] = $pageWidth * 0.20;
                break;
            case 'department':
                $colWidths[$columnId] = $pageWidth * 0.22; // Extra space for long department names
                break;
            case 'serial_no':
                $colWidths[$columnId] = $pageWidth * 0.20;
                break;
            case 'dist_id':
            case 'item_id':
                $colWidths[$columnId] = $pageWidth * 0.06;
                break;
            case 'received_date':
            case 'returned_date':
                $colWidths[$columnId] = $pageWidth * 0.13;
                break;
            case 'borrower_type':
                $colWidths[$columnId] = $pageWidth * 0.10;
                break;
            case 'section':
                $colWidths[$columnId] = $pageWidth * 0.10;
                break;
            case 'MR':
                $colWidths[$columnId] = $pageWidth * 0.15;
                break;
            default:
                $colWidths[$columnId] = $pageWidth * 0.09;
                break;
        }
    }

    // Adjust widths to make sure they sum to page width
    $totalWidth = array_sum($colWidths);
    $scaleFactor = $pageWidth / $totalWidth;

    foreach ($colWidths as $key => $width) {
        $colWidths[$key] = $width * $scaleFactor;
    }

    // Table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(220, 220, 220);

    foreach ($selectedColumns as $columnId) {
        if (isset($columnMapping[$columnId])) {
            $pdf->Cell($colWidths[$columnId], 10, $columnMapping[$columnId], 1, 0, 'C', true);
        }
    }
    $pdf->Ln();

    // Table data
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(245, 245, 245);
    $rowColor = false;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            foreach ($selectedColumns as $columnId) {
                // Handle special cases for formatting or default values
                if ($columnId == 'returned_date' && empty($row[$columnId])) {
                    $value = 'Not Returned';
                } else if ($columnId == 'department' && empty($row[$columnId])) {
                    $value = 'Unknown';
                } else {
                    $value = $row[$columnId] ?? '';
                }

                // Format dates
                if (($columnId == 'received_date' || $columnId == 'returned_date') && !empty($value) && $value != 'Not Returned') {
                    $date = new DateTime($value);
                    $value = $date->format('M d, Y');
                }

                // Calculate cell settings based on column type
                $align = 'L'; // Default left alignment
                $height = 8;  // Default height

                // Set specific alignment per column type
                if (in_array($columnId, ['dist_id', 'item_id'])) {
                    $align = 'C'; // Center align IDs
                } else if ($columnId == 'serial_no') {
                    $align = 'C'; // Center align serial numbers
                }

                // Handle multiline cells for text that might overflow
                if (in_array($columnId, ['department', 'borrower_name', 'item_name', 'MR'])) {
                    // Use MultiCell for these columns that might contain long text
                    $x = $pdf->GetX();
                    $y = $pdf->GetY();

                    // Store current position
                    $startX = $pdf->GetX();
                    $startY = $pdf->GetY();

                    // Calculate cell height needed for this text
                    $cellHeight = $pdf->getStringHeight($colWidths[$columnId], $value);
                    $height = max(8, $cellHeight); // At least 8mm high

                    // If tall cell would cause page break, do it manually
                    if ($startY + $height > $pdf->getPageHeight() - 15) {
                        $pdf->AddPage();
                        $startY = $pdf->GetY();
                    }

                    // Draw the background if needed
                    if ($rowColor) {
                        $pdf->SetFillColor(245, 245, 245);
                        $pdf->Rect($startX, $startY, $colWidths[$columnId], $height, 'F');
                    }

                    // Output the text
                    $pdf->MultiCell($colWidths[$columnId], $height, $value, 1, $align, false);

                    // Move to the right side of the cell
                    $pdf->SetXY($startX + $colWidths[$columnId], $startY);
                } else {
                    // Use regular Cell for other columns
                    $pdf->Cell($colWidths[$columnId], $height, $value, 1, 0, $align, $rowColor);
                }
            }
            $pdf->Ln();
            $rowColor = !$rowColor; // Alternate row colors
        }
    } else {
        // No data found
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell($pageWidth, 10, 'No available items found', 1, 1, 'C');
    }

    // Add summary information
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 8, "Summary Information:", 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, "Total Number of Available Items: " . $result->num_rows, 0, 1);
    $pdf->Cell(0, 8, "Report Generated By: System", 0, 1);

    // Add footer with page numbers
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 0, 'C');

    // Close database connection
    $stmt->close();
    $conn->close();

    // Output the PDF
    // First make sure we haven't sent any output yet
    if (ob_get_length()) ob_clean();

    // Output PDF to browser (I = inline, D = download)
    $pdf->Output('available_items_report_' . date('Y-m-d') . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    // Handle any errors
    echo "Error generating PDF: " . $e->getMessage();
    // You might want to log this error as well
    if (isset($conn)) $conn->close();
    exit;
}
