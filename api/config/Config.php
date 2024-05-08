<?php

define("PAYPAL_CREDENTIALS", array(
	"sandbox" => [
		"client_id" => "Abqhij49wrgTDvmw5nHjgGqvWJG62Umbf2yeUcmVeYcYNZQ2Gs3f4MZis0mWT4eapLdUyw9XUPLiBfmW",
		"client_secret" => "EMTG4xNqiamsStLRJ9MFJWBsH6uZ_aEk2qjwhC32AtM91yAV7Lf1hL7oK8l8SKYfqoSdc_sJCcJOSs79"
	],
	"production" => [
		"client_id" => "",
		"client_secret" => ""
	]
));

define("PAYPAL_ENVIRONMENT", "sandbox");

define("PAYPAL_ENDPOINTS", array(
	"sandbox" => "https://api-m.sandbox.paypal.com",
	"production" => "https://api-m.paypal.com"
));

if(isset($_SERVER['SERVER_NAME'])) {
    $url = @($_SERVER["HTTPS"] != 'on') ? 'http://' . $_SERVER["SERVER_NAME"] : 'https://' . $_SERVER["SERVER_NAME"];
    $url .= ($_SERVER["SERVER_PORT"] !== 80) ? ":" . $_SERVER["SERVER_PORT"] : "";
    $url .= $_SERVER["REQUEST_URI"];
} else {
    $url = "";
}

define("URL", array(
    "current" => $url,
    "services" => array(
        "orderCreate" => 'api/createOrder.php',
        "orderGet" => 'api/getOrderDetails.php',
        "orderPatch" => 'api/updateOrder.php',
        "orderCapture" => 'api/capturePaymentForOrder.php'
    ),
    "redirectUrls" => array(
        "returnUrl" => 'pages/success.php',
        "cancelUrl" => 'pages/cancel.php',
    )
));