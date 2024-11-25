<?php
// Configuration
$consumerKey = 'your_consumer_key';
$consumerSecret = 'your_consumer_secret';
$shortcode = 'your_shortcode';
$passkey = 'your_passkey';
$amount = 'amount';
$phone = '2547XXXXXXXX';

// Function to get access token
function getAccessToken($consumerKey, $consumerSecret) {
    $url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response)->access_token;
}

// Function to initiate STK Push
function initiateSTKPush($accessToken, $shortcode, $passkey, $amount, $phone) {
    $url = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);

    $data = [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $shortcode,
        'PhoneNumber' => $phone,
        'CallBackURL' => 'https://your_callback_url',
        'AccountReference' => 'SchoolFees',
        'TransactionDesc' => 'Paying School Fees'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response);
}

// Get access token
$accessToken = getAccessToken($consumerKey, $consumerSecret);

// Initiate STK Push
$response = initiateSTKPush($accessToken, $shortcode, $passkey, $amount, $phone);

print_r($response);
?>
