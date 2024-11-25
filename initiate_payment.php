<?php 
include 'get_access_token.php';

// Database connection
include 'db.php';



function initiateSTKPush($accessToken, $shortcode, $passkey, $amount, $phone, $businessTill, $conn) {
    if (!$accessToken) {
        die('Access token is invalid or not available.');
    }

    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'; // Ensure the correct endpoint is used

    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $data = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $businessTill,
        'PhoneNumber' => $phone,
        'CallBackURL' => 'https://5e60-41-212-68-82.ngrok-free.app/Mpesa%20Payment/callback_handle.php',
        'AccountReference' => 'SchoolFees',
        'TransactionDesc' => 'Paying School Fees'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
    $response = curl_exec($ch);

    if ($response === false) {
        die('Curl error: ' . curl_error($ch));
    }

    curl_close($ch);

    // Decode the response
    $response = json_decode($response, true);

    // Log the response for debugging
    error_log("STK Push Response: " . print_r($response, true));

    // Check if response is null or not an array
    if (is_null($response) || !is_array($response)) {
        error_log("The response is invalid. Please check the API call.");
        return; // Exit or handle this situation
    }

    // Prepare the data to be inserted into the database
    $status = 'Failed';
    $merchantRequestID = $response['MerchantRequestID'] ?? null;
    $checkoutRequestID = $response['CheckoutRequestID'] ?? null;

    // Log the retrieved IDs for debugging
    error_log("Merchant Request ID: " . $merchantRequestID);
    error_log("Checkout Request ID: " . $checkoutRequestID);

    // Check for the response code
    $resultCode = $response['ResponseCode'] ?? null;
    $resultDesc = $response['ResponseDescription'] ?? 'No description available'; // Set a default description
    $mpesaReceiptNumber = $response['MpesaReceiptNumber'] ?? null;
    $transactionDate = $response['TransactionDate'] ?? date('Y-m-d H:i:s'); // Set to current time if not available

    // Log the result code and description for debugging
    error_log("Result Code: " . $resultCode);
    error_log("Result Description: " . $resultDesc);

    if ($resultCode === '0') {
        $status = 'Pending';
    } else {
        $status = 'Failed';
        error_log("Failed response: " . print_r($response, true));
    }

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO payments (phone, amount, merchant_request_id, checkout_request_id, status) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("SQL prepare error: " . $conn->error);
    }

    // Bind parameters including the new fields
    $stmt->bind_param("sdsss", $phone, $amount, $merchantRequestID, $checkoutRequestID, $status);

    if (!$stmt->execute()) {
        error_log("SQL execution error: " . $stmt->error);
    }

    $stmt->close();

    return $response;
}

// Retrieve form data
$phone = preg_replace('/[^0-9]/', '', $_POST['phone']); // Clean up phone number
error_log("Raw phone input: " . $_POST['phone']); // Log raw input for debugging
error_log("Cleaned phone input: " . $phone); // Log cleaned input for debugging

// Updated validation
if (!preg_match('/^(?:\+254|254|0)?[7-9][0-9]{8}$/', $phone)) {
    echo json_encode(['error' => 'Invalid phone number format.']);
    exit;
}

// Log the amount
$amount = $_POST['amount'];
if (!is_numeric($amount) || $amount <= 0) {
    echo json_encode(['error' => 'Amount must be a positive number.']);
    exit;
}

// Logging for debugging purposes
error_log("Initiating payment: Phone: $phone, Amount: $amount");

// Use your actual values
$shortcode = ''; // Business Shortcode for PayBill
$passkey = ''; // Replace with your actual passkey
$businessTill = ''; // Your business short code or till number

// Call the initiateSTKPush function
$response = initiateSTKPush($accessToken, $shortcode, $passkey, $amount, $phone, $businessTill, $conn);

// Provide user feedback based on the response
if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
    echo json_encode(['success' => 'Payment initiated successfully.', 'data' => $response]);
} else {
    echo json_encode(['error' => 'Failed to initiate payment.', 'details' => $response]); // Include details if available
}

$conn->close();
?>
