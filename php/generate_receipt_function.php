<?php

/**
 * ICT Acknowledgement Receipt Generator
 * 
 * This function generates an ICT acknowledgement receipt using TCPDF
 * Uses an external HTML template file with improved image handling
 */
function generateICTReceipt($receiptData)
{
    try {
        error_log("Starting PDF generation process");
        
        // Include TCPDF library
        $tcpdfPath = dirname(__DIR__) . '/TCPDF-main/tcpdf.php';
        if (!file_exists($tcpdfPath)) {
            error_log("TCPDF library not found at: " . realpath($tcpdfPath));
            throw new Exception("TCPDF library not found");
        }
        error_log("TCPDF library found");
        require_once($tcpdfPath);

        // Create new TCPDF document
        error_log("Creating new TCPDF instance");
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('PLP Management Information System Office');
        $pdf->SetAuthor('PLP-MISO');
        $pdf->SetTitle('ICT Acknowledgement Receipt');
        $pdf->SetSubject('ICT Acknowledgement Receipt');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(10, 10, 10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 15);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Get the HTML template
        $templatePath = dirname(__DIR__) . '/pages/receipt_template.html';
        error_log("Loading template from: " . realpath($templatePath));
        if (!file_exists($templatePath)) {
            error_log("Template file not found at: " . realpath($templatePath));
            throw new Exception("Receipt template not found");
        }
        $html = file_get_contents($templatePath);
        if ($html === false) {
            error_log("Failed to read template file");
            throw new Exception("Failed to read template file");
        }

        // Replace the logo img tag with an empty td
        $html = preg_replace('/<img[^>]+src="[^"]*plp-logo.png"[^>]*>/', '', $html);

        // Replace date placeholder
        $html = str_replace("' . date('d-M-y') . '", date('d-M-y'), $html);

        // Replace user information placeholders
        $html = str_replace(
            '<td class="value" id="name"></td>',
            '<td class="value" id="name">' . htmlspecialchars($receiptData['name']) . '</td>',
            $html
        );

        $html = str_replace(
            '<td class="value" id="office-dept"></td>',
            '<td class="value" id="office-dept">' . htmlspecialchars($receiptData['office']) . '</td>',
            $html
        );

        $html = str_replace(
            '<td class="value" id="section"></td>',
            '<td class="value" id="section">' . htmlspecialchars($receiptData['section']) . '</td>',
            $html
        );

        $html = str_replace(
            '<td class="value" id="email-address"></td>',
            '<td class="value" id="email-address">' . htmlspecialchars($receiptData['email']) . '</td>',
            $html
        );

        $html = str_replace(
            '<td class="value" id="contact-no"></td>',
            '<td class="value" id="contact-no">' . htmlspecialchars($receiptData['contact']) . '</td>',
            $html
        );

        // Build the items row
        $itemRows = '';
        foreach ($receiptData['items'] as $index => $item) {
            $description = htmlspecialchars($item['description']);
            $quantity = htmlspecialchars($item['quantity']);
            $receivedDate = htmlspecialchars($item['receivedDate']);
            $returnedDate = htmlspecialchars($item['returnedDate']);
            $remarks = htmlspecialchars($item['remarks']);

            $itemRows .= '<tr>
                <td style="text-align: center">' . ($index + 1) . '</td>
                <td>' . nl2br($description) . '</td>
                <td style="text-align: center">' . $quantity . '</td>
                <td style="text-align: center">' . $receivedDate . '</td>
                <td style="text-align: center">' . $returnedDate . '</td>
                <td>' . $remarks . '</td>
            </tr>';

            if (!empty($receiptData['bagDesc'])) {
                $itemRows .= '<tr>
                    <td style="text-align: center">' . ($index + 2) . '</td>
                    <td>' . htmlspecialchars($receiptData['bagItemName']) . ', ' . htmlspecialchars($receiptData['bagDesc']) . '</td>
                    <td style="text-align: center">' . htmlspecialchars($receiptData['items'][0]['quantity']) . '</td>
                    <td style="text-align: center">' . htmlspecialchars($receiptData['items'][0]['receivedDate']) . '</td>
                    <td style="text-align: center">' . htmlspecialchars($receiptData['items'][0]['returnedDate']) . '</td>
                    <td>' . htmlspecialchars($receiptData['items'][0]['remarks']) . '</td>
                </tr>';
            }
        }

        // Replace the item placeholder row
        $html = preg_replace(
            '/<tr>\s*<td style="text-align: center" id="item-no">placeholder<\/td>.*?<\/tr>/s',
            $itemRows,
            $html
        );

        // Process borrower photo
        $photoWidth = 0;
        $photoHeight = 0;
        $photoTempFile = null;
        
        if (!empty($receiptData['imageData']) && !empty($receiptData['imageType'])) {
            error_log("Processing borrower photo");
            // Create a temporary file for the image
            $photoTempFile = tempnam(sys_get_temp_dir(), 'borrower_photo');
            if (file_put_contents($photoTempFile, base64_decode($receiptData['imageData']))) {
                try {
                    // Get image dimensions
                    $imgInfo = getimagesize($photoTempFile);
                    if ($imgInfo !== false) {
                        // Calculate dimensions to fit in the cell while maintaining aspect ratio
                        $maxWidth = 28; // mm
                        $maxHeight = 28; // mm
                        
                        $ratio = $imgInfo[0] / $imgInfo[1];
                        if ($ratio > 1) {
                            $photoWidth = $maxWidth;
                            $photoHeight = $photoWidth / $ratio;
                        } else {
                            $photoHeight = $maxHeight;
                            $photoWidth = $photoHeight * $ratio;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Error processing photo: " . $e->getMessage());
                }
            }
        }

        // Replace photo placeholder with empty cell
        $html = str_replace('[PHOTO PLACEHOLDER]', '', $html);

        // Write the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');

        // Add the PLP logo
        $logoPath = dirname(__DIR__) . '/assets/images/plp-logo.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 12, 25, 25, '', '', '', false, 300, '', false, false, 0);
        }

        // Add the borrower photo if we have it
        if ($photoTempFile && $photoWidth > 0 && $photoHeight > 0) {
            $pdf->Image($photoTempFile, 167, 45, $photoWidth, $photoHeight, '', '', '', false, 300, '', false, false, 0);
            unlink($photoTempFile);
        }

        // Add QR code if available
        if (!empty($receiptData['qrContent'])) {
            error_log("QR Code Content: " . $receiptData['qrContent']);
            $style = array(
                'border' => true,
                'padding' => 0,
                'fgcolor' => array(0, 0, 0),
                'bgcolor' => array(255, 255, 255)
            );
            $pdf->write2DBarcode($receiptData['qrContent'], 'QRCODE,H', 155, 190, 30, 30, $style, 'N');
        } else {
            error_log("No QR code content found in receipt data");
        }

        // Return the PDF content
        return $pdf->Output('', 'S');

    } catch (Exception $e) {
        error_log("PDF Generation Error in detail: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        throw new Exception("PDF Generation Error: " . $e->getMessage());
    }
}
