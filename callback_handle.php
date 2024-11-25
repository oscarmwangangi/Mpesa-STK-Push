<?php
// Database connection
include 'db.php';

// Get the raw POST data from the callback
$data = file_get_contents('php://input');

// Decode the JSON data
$transaction = json_decode($data, true);

// Check if transaction data exists and extract the details you need
$merchantRequestID = $transaction['Body']['stkCallback']['MerchantRequestID'] ?? null;
$checkoutRequestID = $transaction['Body']['stkCallback']['CheckoutRequestID'] ?? null;
$resultCode = $transaction['Body']['stkCallback']['ResultCode'] ?? null;
$resultDesc = $transaction['Body']['stkCallback']['ResultDesc'] ?? null;

// Initialize variables
$amount = $mpesaReceiptNumber = $transactionDate = $phoneNumber = null;

// Check if the transaction was successful and CallbackMetadata exists
if ($resultCode == 0 && isset($transaction['Body']['stkCallback']['CallbackMetadata']['Item'])) {
    $items = $transaction['Body']['stkCallback']['CallbackMetadata']['Item'];

    // Loop through items to retrieve the required values based on their order or name
    foreach ($items as $item) {
        $itemName = $item['Name'] ?? null;
        $itemValue = $item['Value'] ?? null;

        switch ($itemName) {
            case 'Amount':
                $amount = $itemValue;
                break;
            case 'MpesaReceiptNumber':
                $mpesaReceiptNumber = $itemValue;
                break;
            case 'TransactionDate':
                $transactionDate = $itemValue;
                break;
            case 'PhoneNumber':
                $phoneNumber = $itemValue;
                break;
        }
    }
}

// Determine status based on resultCode
$status = ($resultCode == 0) ? 'Completed' : 'Failed';

// Prepare and execute the SQL statement
// Prepare and execute the SQL statement
$stmt = $conn->prepare("UPDATE payments SET result_code = ?, result_desc = ?, mpesa_receipt_number = ?, transaction_date = ?, status = ? WHERE checkout_request_id = ?");
if (!$stmt) {
    error_log("SQL prepare error: " . $conn->error);
    http_response_code(500); // Internal Server Error
    exit;
}

$stmt->bind_param("isssss", $resultCode, $resultDesc, $mpesaReceiptNumber, $transactionDate, $status, $checkoutRequestID);
if (!$stmt->execute()) {
    error_log("SQL execution error: " . $stmt->error);
    http_response_code(500); // Internal Server Error
    exit;
}

// Respond with 200 OK to M-Pesa
http_response_code(200);
$stmt->close();

?>
