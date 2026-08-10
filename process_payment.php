<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 1. Pokea data kutoka kwenye Form
$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    $input = $_POST;
}

$phone = $input['phone'] ?? $input['phone_number'] ?? '';
$amount = $input['amount'] ?? '';
$provider = $input['provider'] ?? 'Airtel';

if (empty($phone) || empty($amount)) {
    echo json_encode(["status" => "error", "message" => "Taarifa hazijakamilika"]);
    exit;
}

// Rekebisha namba ya simu ianze na 255
$phone = preg_replace('/[^0-9]/', '', $phone);
if (substr($phone, 0, 1) == "0") {
    $phone = "255" . substr($phone, 1);
}

// 2. Taarifa Zako za AzamPay
$appName = "Dr william duka";
$clientId = "4748626c-10cd-4cc8-95d5-6f552733abf6";
$clientSecret = "Zt2FEK4sXqJPSwJ8jHocXNgR46n0EK8PGJvGgbguXnlWF/mo4y/QIfa9JO9CVM+i+yQCiY4xM/yW/xaJkzx+eVNZ1neUIAd5Aopn+aLp0eLkbOsCRFBnSgK/dfVpPBKh+cSSwHSZ2kAmPvL+kWzr9Z/C6HJq6NYFVxTf9/gnn3WiFYyoQscBUNPVIXsmnji32ObBGk+qG3uR3iyiI8jw7Mw8p8b8MbBNx+NTG5JtK3j9L2NP1+0qJlYNBqLeS8PurisP2PJ02UZx9oG3sC+FNQP/SgnAX8nq2WmFW/NRDfc6RNOrx2eSX9Xo5xcfBVfuiGo7EPd/Bum8ifKltN6sEA24vYWkDaFFfUN/Q2om2xybV/rojekKkCFaXQBJDU0NyW8P12j8/+jQ/E4g8SxCAc2HElWuy/K0IOA6JnSztZqbHrDV0OYNsW8hbwmbUo0RsA09jHmcjPrg+od2J3j//PtqhU2nCTW39nmVC6VGjHfgtTQ6zLwGsrFq1FvdY12YcQ1SulA5pWhqFgaFNcXImvJKryyp0aaI3+fzJzMRq7qQ11SMAnGtLoxKPSxGaACh5qA9+nCFlvKGWKKyXmeRIOteQiBYIaCeifZ/j9JYAaDSoBgXCF0hVZzm3xwrDx5bjgQUltiyw8lS5syfy5/5ToaqrJMkrVDMjFDuGU7NmJk=";

// 3. Omba Access Token kutoka LIVE Authenticator ya AzamPay
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

// 4. Kutuma Ombi la Checkout kwenye AzamPay LIVE
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
