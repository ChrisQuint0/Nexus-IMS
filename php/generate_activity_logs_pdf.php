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

    // Create new PDF document - Use landscape orientation
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

    // Set margins - REDUCED for more space
    $pdf->SetMargins(10, 10, 10);
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
    $pageWidth = $pdf->GetPageWidth() - 20; // 20mm margins total (10mm each side)
    $colWidths = array();

    // IMPROVED - Better column width allocation based on content expectations
    // First pass: Set minimum widths based on column types
    foreach ($selectedColumns as $columnId) {
        switch ($columnId) {
            case 'log_id':
                $colWidths[$columnId] = 15; // fixed width
                break;
            case 'user_id':
                $colWidths[$columnId] = 15; // fixed width
                break;
            case 'username':
                $colWidths[$columnId] = 30; // medium width 
                break;
            case 'email':
                $colWidths[$columnId] = 50; // large width for emails
                break;
            case 'department':
                $colWidths[$columnId] = 40; // medium-large width
                break;
            case 'user_type':
                $colWidths[$columnId] = 25; // medium width
                break;
            case 'action':
                $colWidths[$columnId] = 75; // largest width for action text
                break;
            case 'timestamp':
                $colWidths[$columnId] = 35; // medium-fixed width
                break;
            default:
                $colWidths[$columnId] = 25; // default width
                break;
        }
    }

    // Calculate total assigned width
    $totalAssignedWidth = array_sum($colWidths);

    // Second pass: Adjust widths proportionally if needed
    if ($totalAssignedWidth > $pageWidth) {
        $scaleFactor = $pageWidth / $totalAssignedWidth;
        foreach ($colWidths as $key => $width) {
            $colWidths[$key] = floor($width * $scaleFactor);
        }
    }
    // Ensure minimum widths for essential columns
    if (isset($colWidths['log_id']) && $colWidths['log_id'] < 15) $colWidths['log_id'] = 15;
    if (isset($colWidths['user_id']) && $colWidths['user_id'] < 15) $colWidths['user_id'] = 15;

    // Recalculate and distribute any remaining space
    $totalAdjustedWidth = array_sum($colWidths);
    $remainingWidth = $pageWidth - $totalAdjustedWidth;

    // If there's remaining space, give it to the action column (which often needs more space)
    if ($remainingWidth > 0 && isset($colWidths['action'])) {
        $colWidths['action'] += $remainingWidth;
    }

    // Table header
    $pdf->SetFont('helvetica', 'B', 9); // SMALLER font
    $pdf->SetFillColor(220, 220, 220);

    foreach ($selectedColumns as $columnId) {
        if (isset($columnMapping[$columnId])) {
            $pdf->Cell($colWidths[$columnId], 8, $columnMapping[$columnId], 1, 0, 'C', true);
        }
    }
    $pdf->Ln();

    // Table data - SMALLER font for content
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(245, 245, 245);
    $rowColor = false;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Calculate the maximum row height needed for this row
            $maxRowHeight = 7; // Minimum row height

            // First pass: check each cell to determine maximum needed height
            foreach ($selectedColumns as $columnId) {
                $value = $row[$columnId] ?? '';

                // Format timestamp if needed
                if ($columnId == 'timestamp' && !empty($value)) {
                    $date = new DateTime($value);
                    $value = $date->format('Y-m-d H:i:s');
                }

                // For columns that might contain longer text
                if (in_array($columnId, ['action', 'email', 'department'])) {
                    // Get height needed for this content
                    $cellHeight = $pdf->getStringHeight($colWidths[$columnId], $value);
                    $maxRowHeight = max($maxRowHeight, $cellHeight + 2); // Add padding
                }
            }

            // Check if we need a page break
            if ($pdf->GetY() + $maxRowHeight > $pdf->getPageHeight() - 15) {
                $pdf->AddPage();

                // Repeat the header on the new page
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(220, 220, 220);
                foreach ($selectedColumns as $columnId) {
                    if (isset($columnMapping[$columnId])) {
                        $pdf->Cell($colWidths[$columnId], 8, $columnMapping[$columnId], 1, 0, 'C', true);
                    }
                }
                $pdf->Ln();
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetFillColor(245, 245, 245);
            }

            // Store current Y position
            $startY = $pdf->GetY();

            // Second pass: Draw all cells with the same height
            foreach ($selectedColumns as $columnId) {
                $value = $row[$columnId] ?? '';

                // Format timestamp if needed
                if ($columnId == 'timestamp' && !empty($value)) {
                    $date = new DateTime($value);
                    $value = $date->format('Y-m-d H:i:s');
                }

                // Set alignment based on column type
                $align = 'L'; // Default left alignment
                if (in_array($columnId, ['log_id', 'user_id', 'timestamp'])) {
                    $align = 'C'; // Center align IDs and timestamps
                }

                // For columns that might need multiline
                if (in_array($columnId, ['action', 'email', 'department'])) {
                    $x = $pdf->GetX();
                    $pdf->MultiCell($colWidths[$columnId], $maxRowHeight, $value, 1, $align, $rowColor);
                    // Move position for the next cell
                    $pdf->SetXY($x + $colWidths[$columnId], $startY);
                } else {
                    // Regular cells
                    $pdf->Cell($colWidths[$columnId], $maxRowHeight, $value, 1, 0, $align, $rowColor);
                }
            }

            // Move to next line
            $pdf->SetY($startY + $maxRowHeight);
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
