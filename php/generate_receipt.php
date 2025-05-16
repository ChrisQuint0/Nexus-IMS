<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display but keep logging
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/php_errors.log'); // Set a specific error log file

/**
 * ICT Acknowledgement Receipt Generator Endpoint
 * 
 * This file serves as the endpoint to generate receipts based on
 * submitted form data from the add_borrower.html page.
 */

// Include the function file
require_once('generate_receipt_function.php');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Clear any previous output
        while (ob_get_level()) ob_end_clean();
        
        if (!isset($_POST['receiptData'])) {
            throw new Exception('Missing receipt data');
        }

        // Decode the JSON data
        $receiptData = json_decode($_POST['receiptData'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
        }

        // Check for required fields
        $requiredFields = ['name', 'office', 'section', 'email', 'contact', 'items'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (empty($receiptData[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missingFields));
        }

        // Check if items array is not empty and has required data
        if (empty($receiptData['items']) || empty($receiptData['items'][0]['description'])) {
            throw new Exception('No valid items specified for the receipt');
        }

        // Handle photo upload if available
        if (isset($_FILES['borrower_photo']) && $_FILES['borrower_photo']['error'] === UPLOAD_ERR_OK) {
            error_log("Processing uploaded photo");
            $imageData = file_get_contents($_FILES['borrower_photo']['tmp_name']);
            if ($imageData !== false) {
                $receiptData['imageData'] = base64_encode($imageData);
                $receiptData['imageType'] = $_FILES['borrower_photo']['type'];
                error_log("Photo data added to receipt data");
            } else {
                error_log("Failed to read photo data from uploaded file");
            }
        } else {
            error_log("No photo uploaded or upload error occurred");
            if (isset($_FILES['borrower_photo'])) {
                error_log("Photo upload error code: " . $_FILES['borrower_photo']['error']);
            }
        }

        // Generate the PDF
        $pdfContent = generateICTReceipt($receiptData);
        
        // Ensure we have valid PDF content
        if (empty($pdfContent) || strlen($pdfContent) < 100) {
            error_log("Generated PDF content is invalid or too small: " . strlen($pdfContent) . " bytes");
            throw new Exception("Failed to generate valid PDF content");
        }

        // Clear any buffered output
        while (ob_get_level()) ob_end_clean();
        
        // Set PDF headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="receipt.pdf"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Content-Length: ' . strlen($pdfContent));
        
        // Output the PDF content
        echo $pdfContent;
        exit();

    } catch (Exception $e) {
        // Clear any partial output
        while (ob_get_level()) ob_end_clean();
        
        // Log the error
        error_log("Receipt generation error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        // Send JSON error response
        header('Content-Type: application/json');
        http_response_code(500); // Set appropriate error status code
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
} else {
    header('Content-Type: application/json');
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}
