<?php
require_once 'jwt.php';

$key = 'shaqueenaAileenTaurensia';
$adminName = 'aileen';
$msgUnauthorized = 'Access is denied due to invalid credentials.';

function getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

function validateToken($key, $adminName)
{
    $hasil = false;
    $token = null;
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            $token = $matches[1];
        }
    }

    $iss = null;
    $JWT = new JWT;

    if ($token !== null) {
        $json = $JWT->decode($token, $key);
        $data = json_decode($json, true);
        $iss = $data['iss'];
        $exp = $data['exp'];
        $skrg = round(microtime(true) * 1000);

        // if ($skrg<$exp && $iss === $adminName){
        if ($skrg < $exp) {
            $hasil = true;
        }
    }

    return $hasil;
}

?>