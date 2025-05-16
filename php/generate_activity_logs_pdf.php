<?php
// Set error reporting to catch potential issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get parameters from POST request
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';
$departmentId = isset($_POST['departmentId']) ? intval($_POST['departmentId']) : 0;
$onlyDepartment = isset($_POST['onlyDepartment']) ? intval($_POST['onlyDepartment']) : 0;

// Get selected columns, defaulting to all columns if none selected
$selectedColumns = isset($_POST['columns']) ? json_decode($_POST['columns'], true) : [];

// Default to these columns if none selected
if (empty($selectedColumns)) {
    $selectedColumns = ['username', 'action', 'timestamp'];
}

// Include database connection
include 'db.php';

// Define column mapping between ID and display name
$columnMapping = [
    'log_id' => 'Log ID',
    'user_id' => 'User ID',
    'username' => 'Username',
    'email' => 'Email',
    'department' => 'Department',
    'user_type' => 'User Type',
    'action' => 'Action',
    'timestamp' => 'Timestamp'
];

// Define SQL column mapping (database field names)
$sqlColumns = [
    'log_id' => 'al.id',
    'user_id' => 'al.user_id',
    'username' => 'u.username',
    'email' => 'u.email',
    'department' => 'd.department_name',
    'user_type' => 'u.user_type',
    'action' => 'al.action',
    'timestamp' => 'al.timestamp'
];

// Build the SQL SELECT part with only selected columns
$selectColumns = [];
foreach ($selectedColumns as $columnId) {
    if (isset($sqlColumns[$columnId])) {
        $selectColumns[] = $sqlColumns[$columnId] . " AS " . $columnId;
    }
}
$selectClause = implode(", ", $selectColumns);

// Base query with joins
$baseQuery = "FROM 
    audit_log al
LEFT JOIN users u ON al.user_id = u.user_id
LEFT JOIN departments d ON u.department_id = d.department_id
WHERE 1=1";

// Initialize parameters array for prepared statement
$params = [];
$paramTypes = "";

// Add date filters if provided
if (!empty($startDate)) {
    $baseQuery .= " AND al.timestamp >= ?";
    $params[] = $startDate . " 00:00:00";
    $paramTypes .= "s";
}

if (!empty($endDate)) {
    $baseQuery .= " AND al.timestamp <= ?";
    $params[] = $endDate . " 23:59:59";
    $paramTypes .= "s";
}

// Add department filter if specified
if ($onlyDepartment > 0) {
    $baseQuery .= " AND u.department_id = ?";
    $params[] = $onlyDepartment;
    $paramTypes .= "i";
}
// When onlyDepartment is 0, no filter is added, so all departments are included

// Order by timestamp descending
$baseQuery .= " ORDER BY al.timestamp DESC";

try {
    // Final SQL query
    $sql = "SELECT " . $selectClause . " " . $baseQuery;

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // Bind parameters if there are any
    if (!empty($params)) {
        $stmt->bind_param($paramTypes, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    // Get department name for the report title
    $departmentName = 'All Departments';
    if ($onlyDepartment > 0) {
        $deptQuery = "SELECT department_name FROM departments WHERE department_id = ?";
        $deptStmt = $conn->prepare($deptQuery);
        $deptStmt->bind_param("i", $onlyDepartment);
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
    $pdf->SetCreator('Audit Log System');
    $pdf->SetAuthor('System Administrator');
    $pdf->SetTitle('Audit Logs Report');
    $pdf->SetSubject('System Activity Audit Logs');
    $pdf->SetKeywords('Audit, Logs, Activity, Report');

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
    $title = "System Activity Audit Logs";
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, "Department: " . $departmentName, 0, 1, 'C');

    // Add date range to title if provided
    if (!empty($startDate) && !empty($endDate)) {
        $pdf->Cell(0, 8, "Period: " . date('F d, Y', strtotime($startDate)) . " to " . date('F d, Y', strtotime($endDate)), 0, 1, 'C');
    } elseif (!empty($startDate)) {
        $pdf->Cell(0, 8, "From: " . date('F d, Y', strtotime($startDate)), 0, 1, 'C');
    } elseif (!empty($endDate)) {
        $pdf->Cell(0, 8, "Until: " . date('F d, Y', strtotime($endDate)), 0, 1, 'C');
    }

    $pdf->Cell(0, 8, "Date Generated: " . date('F d, Y'), 0, 1, 'C');
    $pdf->Ln(5);

    // Calculate column widths based on selected columns
    $numColumns = count($selectedColumns);
    $pageWidth = $pdf->GetPageWidth() - 30; // 30mm margins total (15mm each side)
    $colWidths = array();

    // Assign different widths based on column type
    foreach ($selectedColumns as $columnId) {
        switch ($columnId) {
            case 'log_id':
            case 'user_id':
                $colWidths[$columnId] = $pageWidth * 0.07;
                break;
            case 'username':
                $colWidths[$columnId] = $pageWidth * 0.15;
                break;
            case 'email':
                $colWidths[$columnId] = $pageWidth * 0.18;
                break;
            case 'department':
                $colWidths[$columnId] = $pageWidth * 0.16;
                break;
            case 'user_type':
                $colWidths[$columnId] = $pageWidth * 0.10;
                break;
            case 'action':
                $colWidths[$columnId] = $pageWidth * 0.25;
                break;
            case 'timestamp':
                $colWidths[$columnId] = $pageWidth * 0.15;
                break;
            default:
                $colWidths[$columnId] = $pageWidth * 0.10;
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
                // Get cell value
                $value = $row[$columnId] ?? '';

                // Format timestamp if needed
                if ($columnId == 'timestamp' && !empty($value)) {
                    $date = new DateTime($value);
                    $value = $date->format('Y-m-d H:i:s');
                }

                // Calculate cell settings based on column type
                $align = 'L'; // Default left alignment
                $height = 8;  // Default height

                // Set specific alignment per column type
                if (in_array($columnId, ['log_id', 'user_id'])) {
                    $align = 'C'; // Center align IDs
                } else if ($columnId == 'timestamp') {
                    $align = 'C'; // Center align timestamps
                }

                // Handle multiline cells for text that might overflow
                if (in_array($columnId, ['action', 'email', 'department'])) {
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
        $pdf->Cell($pageWidth, 10, 'No audit logs found for the selected criteria', 1, 1, 'C');
    }

    // Add summary information
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 8, "Summary Information:", 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, "Total Number of Log Entries: " . $result->num_rows, 0, 1);
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
    $pdf->Output('audit_logs_report_' . date('Y-m-d') . '.pdf', 'I');
    exit;
} catch (Exception $e) {
    // Handle any errors
    echo "Error generating PDF: " . $e->getMessage();
    // You might want to log this error as well
    if (isset($conn)) $conn->close();
    exit;
}
