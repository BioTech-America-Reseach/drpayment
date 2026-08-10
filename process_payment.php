<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true) ?: $_POST;

$phone = $input['phone'] ?? $input['phone_number'] ?? '';
$amount = $input['amount'] ?? '';
$provider = $input['provider'] ?? 'Airtel';

if (empty($phone) || empty($amount)) {
    echo json_encode(["status" => "error", "message" => "Namba ya simu au kiasi kimekosekana."]);
    exit;
}

// Rekebisha Namba ya Simu
$phone = preg_replace('/[^0-9]/', '', $phone);
if (substr($phone, 0, 1) == "0") {
    $phone = "255" . substr($phone, 1);
}

// Taarifa za AzamPay LIVE
$appName = "Dr william duka";
$clientId = "4748626c-10cd-4cc8-95d5-6f552733abf6";
$clientSecret = "Ubvj8gP8cQu/dXBxuUT4LWv9Oha+gUTy22ZCfVse1fQTWjvzOPeBe8md4LPFLv2qYSGM4jlae9eVzYkhS8FyIQPbI8HbPeXR7aAIgz+cknNcfvrrpxX4wIdtsS1Nocm40sFbduAwGeSzVeEu1drwe/MParUXDshMBykexXsrFqie3BmhpDBElncusL+JepKMoB4y4R5ISFJJikqvXdDXu+YZbt6yXRa25Ze34pOg6lumn6+8d9ZbVcfxQr8yJQbGVevDwWRONJSNpiVrrPCF3sxE4htX6l58+AFsy+/WtrFO1wSG/CwTUKOnmt+m5g6fMM75qbhmv+Lc+wrjU4JDjptvSIVfqLAMl0KhY70bTstF+6e/MIkvxzzVuuc0sxF3mU4juRBVRXu0PqeuVvq5WV9lhzIf6DYvQrZIFDYyKAl+WFrlXZyeQVnOuFKwrDV88DHRqYr3IA/L9I4WPzROYiYieiNkYn7dHUc9CGMvOHoi3CIXfZg2lR1qCuHGXZEJwRbqrxtxglxyYQTbP4xJtg+ylhYhCy8rmXM4DXHQJsNhaiZ37czJ8aEDB08rQwEmw7+IRpfGOvzRXd4QmC7N+Js1KTTyy1R/bGK60DcwC/CaHUIXZiKT+MHfSp1wImd0MTSOh0ucavXg3sbOFmIoPCbjtts4OXV8tlWSbLvn+Vk=";

function sendHttpPost($url, $data, $bearerToken = null) {
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n" .
                         "Accept: application/json\r\n" .
                         ($bearerToken ? "Authorization: Bearer {$bearerToken}\r\n" : ""),
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true,
            'timeout' => 30
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result, true) ?: $result;
}

// 1. Kutengeneza Access Token LIVE
$authPayload = [
    "appName" => trim($appName),
    "clientId" => trim($clientId),
    "clientSecret" => trim($clientSecret)
];

$authData = sendHttpPost("https://authenticator.azampay.co.tz/AppAuthentication/generateToken", $authPayload);
$token = is_array($authData) ? ($authData['data']['accessToken'] ?? null) : null;

if (!$token) {
    echo json_encode([
        "status" => "error",
        "message" => "AzamPay Live Authentication Imefeli. Hakikisha Client Secret na App Name ziko sahihi na 'Allow All URLs' imewekwa tiki kule AzamPay Portal.",
        "azampay_response" => $authData
    ]);
    exit;
}

// 2. Kutuma Checkout Request LIVE
$checkoutPayload = [
    "accountNumber" => $phone,
    "amount" => (string)$amount,
    "currency" => "TZS",
    "externalId" => "ORDER-" . time(),
    "provider" => $provider
];

$checkoutResponse = sendHttpPost("https://checkout.azampay.co.tz/azampay/mno/checkout", $checkoutPayload, $token);

echo is_array($checkoutResponse) ? json_encode($checkoutResponse) : $checkoutResponse;
?>
