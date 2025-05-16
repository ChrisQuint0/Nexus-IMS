<?php
// Set error reporting to catch potential issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get parameters from POST request
$onlyDepartment = isset($_POST['onlyDepartment']) ? $_POST['onlyDepartment'] : '';
$today = date('Y-m-d'); // Format: YYYY-MM-DD
$startDate = isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : $today;
$endDate = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : $today;

// Get selected columns, defaulting to all columns if none selected
$selectedColumns = isset($_POST['columns']) ? json_decode($_POST['columns'], true) : [];

// Default to these columns if none selected
if (empty($selectedColumns)) {
    $selectedColumns = ['borrower_name', 'serial_no', 'item_name', 'returned_at', 'item_condition'];
}

// Make sure returned_at is always included in the output
if (!in_array('returned_at', $selectedColumns)) {
    // Add returned_at to the beginning of the array
    array_unshift($selectedColumns, 'returned_at');
}

// Include database connection
include 'db.php';

// Define column mapping between ID and display name
$columnMapping = [
    'log_id' => 'Log ID',
    'returned_at' => 'Returned At',
    'staff' => 'Staff',
    'item_condition' => 'Item Condition',
    'remarks' => 'Remarks',
    'dist_id' => 'Distribution ID',
    'borrower_type' => 'Borrower Type',
    'borrower_name' => 'Borrower Name',
    'serial_no' => 'Serial No',
    'item_name' => 'Item Name',
    'received_date' => 'Received Date',
    'return_date' => 'Return Date',
    'department' => 'Department'
];

// Define SQL column mapping (database field names)
$sqlColumns = [
    'log_id' => 'rl.log_id',
    'returned_at' => 'rl.returned_at',
    'staff' => 'rl.staff',
    'item_condition' => 'rl.item_condition',
    'remarks' => 'rl.remarks',
    'dist_id' => 'gd.dist_id',
    'borrower_type' => 'gd.borrower_type',
    'borrower_name' => "CASE 
                WHEN gd.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                WHEN gd.borrower_type IS NOT NULL THEN CONCAT(e.emp_fname, ' ', e.emp_lname)
                ELSE 'Unknown'
            END",
    'serial_no' => 'i.serial_no',
    'item_name' => 'idesc.item_name',
    'received_date' => 'gd.received_date',
    'return_date' => 'gd.return_date',
    'department' => 'd.department_name'
];

// Build the SQL SELECT part with selected columns
$selectColumns = [];
foreach ($selectedColumns as $columnId) {
    if (isset($sqlColumns[$columnId])) {
        $selectColumns[] = $sqlColumns[$columnId] . " AS " . $columnId;
    }
}
$selectClause = implode(", ", $selectColumns);

try {
    // Base query with joins
    $sql = "SELECT 
                $selectClause
            FROM 
                return_logs rl
            JOIN 
                gadget_distribution gd ON rl.dist_id = gd.dist_id
            LEFT JOIN 
                students s ON gd.stud_rec_id = s.stud_rec_id
            LEFT JOIN 
                employees e ON gd.receiver_id = e.emp_rec_id
            LEFT JOIN 
                items i ON gd.item_id = i.item_id
            LEFT JOIN 
                item_desc idesc ON i.item_desc_id = idesc.item_desc_id
            LEFT JOIN 
                employees emp ON gd.mrep_id = emp.emp_rec_id
            LEFT JOIN 
                departments d ON emp.department_id = d.department_id
            WHERE 
                d.department_id = ?
                AND rl.returned_at BETWEEN ? AND ?
            ORDER BY 
                rl.returned_at DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // Bind parameters: department_id (int), startDate (string), endDate (string)
    $stmt->bind_param("iss", $onlyDepartment, $startDate, $endDate);

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    // Get department name for the report title
    $departmentName = 'Unknown Department';
    $deptQuery = "SELECT department_name FROM departments WHERE department_id = ?";
    $deptStmt = $conn->prepare($deptQuery);
    if ($deptStmt === false) {
        throw new Exception('Prepare department query failed: ' . $conn->error);
    }
    $deptStmt->bind_param("i", $onlyDepartment);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    if ($deptRow = $deptResult->fetch_assoc()) {
        $departmentName = $deptRow['department_name'];
    }
    $deptStmt->close();

    // Format date range for display
    $startDateObj = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    $startDateFormatted = $startDateObj->format('F d, Y');
    $endDateFormatted = $endDateObj->format('F d, Y');
    $dateRange = ($startDate == $endDate) ? $startDateFormatted : "$startDateFormatted to $endDateFormatted";

    // Load TCPDF library
    require_once('../TCPDF-main/tcpdf.php');

    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Inventory System');
    $pdf->SetAuthor('System Administrator');
    $pdf->SetTitle('Returned Items Logs Report');
    $pdf->SetSubject('Inventory of Returned Items Logs');
    $pdf->SetKeywords('Inventory, Returned Items, Logs, Report');

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
    $title = "Returned Items Logs Report";
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, "Department: " . $departmentName, 0, 1, 'C');
    $pdf->Cell(0, 8, "Date Range: " . $dateRange, 0, 1, 'C');
    $pdf->Cell(0, 8, "Report Generated: " . date('F d, Y'), 0, 1, 'C');
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
                $colWidths[$columnId] = $pageWidth * 0.15;
                break;
            case 'returned_at':
                $colWidths[$columnId] = $pageWidth * 0.15;
                break;
            case 'remarks':
                $colWidths[$columnId] = $pageWidth * 0.25; // Extra space for remarks
                break;
            case 'item_condition':
                $colWidths[$columnId] = $pageWidth * 0.12;
                break;
            case 'log_id':
            case 'dist_id':
                $colWidths[$columnId] = $pageWidth * 0.06;
                break;
            case 'received_date':
            case 'return_date':
                $colWidths[$columnId] = $pageWidth * 0.13;
                break;
            case 'borrower_type':
                $colWidths[$columnId] = $pageWidth * 0.10;
                break;
            case 'staff':
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
                if ($columnId == 'return_date' && empty($row[$columnId])) {
                    $value = 'Not Returned';
                } else if ($columnId == 'department' && empty($row[$columnId])) {
                    $value = 'Unknown';
                } else {
                    $value = $row[$columnId] ?? '';
                }

                // Format dates
                if (($columnId == 'received_date' || $columnId == 'return_date') && !empty($value) && $value != 'Not Returned') {
                    $date = new DateTime($value);
                    $value = $date->format('M d, Y');
                } else if ($columnId == 'returned_at' && !empty($value)) {
                    $date = new DateTime($value);
                    $value = $date->format('M d, Y H:i:s');
                }

                // Calculate cell settings based on column type
                $align = 'L'; // Default left alignment
                $height = 8;  // Default height

                // Set specific alignment per column type
                if (in_array($columnId, ['log_id', 'dist_id'])) {
                    $align = 'C'; // Center align IDs
                } else if ($columnId == 'serial_no') {
                    $align = 'C'; // Center align serial numbers
                }

                // Handle multiline cells for text that might overflow
                if (in_array($columnId, ['department', 'borrower_name', 'item_name', 'staff', 'remarks'])) {
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
        $pdf->Cell($pageWidth, 10, 'No returned items found in the selected date range', 1, 1, 'C');
    }

    // Add summary information
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 8, "Summary Information:", 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, "Total Number of Returned Items Logs: " . $result->num_rows, 0, 1);
    $pdf->Cell(0, 8, "Date Range: " . $dateRange, 0, 1);
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
    $pdf->Output('returned_items_report_' . date('Y-m-d') . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    // Handle any errors
    echo "Error generating PDF: " . $e->getMessage();
    // You might want to log this error as well
    if (isset($conn)) $conn->close();
    exit;
}
