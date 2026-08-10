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
$email = $input['email'] ?? 'drwilliamusa@gmail.com';
$firstName = $input['first_name'] ?? 'Mteja';
$lastName = $input['last_name'] ?? 'Duka';

if (empty($phone) || empty($amount)) {
    echo json_encode(["status" => "error", "message" => "Namba ya simu au kiasi kimekosekana."]);
    exit;
}

// Pesapal Credentials Zako Halisi
$consumerKey = "JNre31bX7L2XpRn+Uv9ChT3XsMjoZD+e";
$consumerSecret = "moYmzzSPE50C5QLgUoLcObrYXL4=";

$baseUrl = "https://pay.pesapal.com/v3";

function sendPesapalRequest($url, $data, $token = null) {
    $headers = [
        "Content-Type: application/json",
        "Accept: application/json"
    ];
    if ($token) {
        $headers[] = "Authorization: Bearer " . $token;
    }

    $options = [
        'http' => [
            'header'  => implode("\r\n", $headers),
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
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result, true);
}

// 1. Omba Access Token kutoka Pesapal
$authPayload = [
    "consumer_key" => trim($consumerKey),
    "consumer_secret" => trim($consumerSecret)
];

$authData = sendPesapalRequest($baseUrl . "/api/Auth/RequestToken", $authPayload);
$token = $authData['token'] ?? null;

if (!$token) {
    echo json_encode([
        "status" => "error",
        "message" => "Authentication ya Pesapal imefeli.",
        "pesapal_response" => $authData
    ]);
    exit;
}

// 2. Tengeneza Order Request
$orderPayload = [
    "id" => "ORDER-" . time(),
    "currency" => "TZS",
    "amount" => (float)$amount,
    "description" => "Malipo ya Duka la Dr William",
    "callback_url" => "https://biotech-america-reseach.github.io/drpayment/",
    "billing_address" => [
        "email_address" => $email,
        "phone_number" => $phone,
        "first_name" => $firstName,
        "last_name" => $lastName,
        "country_code" => "TZ"
    ]
];

$orderData = sendPesapalRequest($baseUrl . "/api/Transactions/SubmitOrderRequest", $orderPayload, $token);

if (isset($orderData['redirect_url'])) {
    echo json_encode([
        "status" => "success",
        "redirect_url" => $orderData['redirect_url'],
        "order_tracking_id" => $orderData['order_tracking_id'] ?? null
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Imeshindwa kutengeneza ukurasa wa malipo ya Pesapal.",
        "pesapal_response" => $orderData
    ]);
}
?>
