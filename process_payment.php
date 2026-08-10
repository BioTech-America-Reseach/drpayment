<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. Pokea data kutoka kwenye Form au JSON Payload
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    $input = $_POST;
}

$phone = $input['phone'] ?? $input['phone_number'] ?? '';
$amount = $input['amount'] ?? '';
$provider = $input['provider'] ?? 'Airtel';

if (empty($phone) || empty($amount)) {
    echo json_encode(["status" => "error", "message" => "Taarifa za namba ya simu au kiasi zimekosekana."]);
    exit;
}

// Rekebisha namba ya simu ianze na format ya 255
$phone = preg_replace('/[^0-9]/', '', $phone);
if (substr($phone, 0, 1) == "0") {
    $phone = "255" . substr($phone, 1);
}

// 2. Taarifa Zako Mpya za AzamPay LIVE
$appName = "Dr william duka";
$clientId = "4748626c-10cd-4cc8-95d5-6f552733abf6";
$clientSecret = "Ubvj8gP8cQu/dXBxuUT4LWv9Oha+gUTy22ZCfVse1fQTWjvzOPeBe8md4LPFLv2qYSGM4jlae9eVzYkhS8FyIQPbI8HbPeXR7aAIgz+cknNcfvrrpxX4wIdtsS1Nocm40sFbduAwGeSzVeEu1drwe/MParUXDshMBykexXsrFqie3BmhpDBElncusL+JepKMoB4y4R5ISFJJikqvXdDXu+YZbt6yXRa25Ze34pOg6lumn6+8d9ZbVcfxQr8yJQbGVevDwWRONJSNpiVrrPCF3sxE4htX6l58+AFsy+/WtrFO1wSG/CwTUKOnmt+m5g6fMM75qbhmv+Lc+wrjU4JDjptvSIVfqLAMl0KhY70bTstF+6e/MIkvxzzVuuc0sxF3mU4juRBVRXu0PqeuVvq5WV9lhzIf6DYvQrZIFDYyKAl+WFrlXZyeQVnOuFKwrDV88DHRqYr3IA/L9I4WPzROYiYieiNkYn7dHUc9CGMvOHoi3CIXfZg2lR1qCuHGXZEJwRbqrxtxglxyYQTbP4xJtg+ylhYhCy8rmXM4DXHQJsNhaiZ37czJ8aEDB08rQwEmw7+IRpfGOvzRXd4QmC7N+Js1KTTyy1R/bGK60DcwC/CaHUIXZiKT+MHfSp1wImd0MTSOh0ucavXg3sbOFmIoPCbjtts4OXV8tlWSbLvn+Vk=";

// 3. Pata Access Token kutoka AzamPay Authenticator
$authUrl = "https://authenticator.azampay.co.tz/AppAuthentication/generateToken";
$authPayload = json_encode([
    "appName" => trim($appName),
    "clientId" => trim($clientId),
    "clientSecret" => trim($clientSecret)
], JSON_UNESCAPED_SLASHES);

$ch = curl_init($authUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $authPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$authResponse = curl_exec($ch);
curl_close($ch);

$authData = json_decode($authResponse, true);
$token = $authData['data']['accessToken'] ?? null;

if (!$token) {
    echo json_encode([
        "status" => "error", 
        "message" => "Authentication Imefeli",
        "azampay_response" => $authData ?? $authResponse
    ]);
    exit;
}

// 4. Tuma ombi la Checkout kwa MNO (Mobile Network Operator)
$checkoutUrl = "https://checkout.azampay.co.tz/azampay/mno/checkout";
$checkoutPayload = json_encode([
    "accountNumber" => $phone,
    "amount" => (string)$amount,
    "currency" => "TZS",
    "externalId" => "ORDER-" . time(),
    "provider" => $provider
], JSON_UNESCAPED_SLASHES);

$ch2 = curl_init($checkoutUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $checkoutPayload);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);

$checkoutResponse = curl_exec($ch2);
curl_close($ch2);

echo $checkoutResponse;
?>
