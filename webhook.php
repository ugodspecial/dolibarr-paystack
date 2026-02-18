<?php
/* Copyright (C) 2024 Paystack Module for Dolibarr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    webhook.php
 * \ingroup paystack
 * \brief   Paystack webhook handler for payment notifications
 */

// Prevent direct access
if (!defined('NOREQUIREUSER')) {
    define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIRESOC')) {
    define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
    define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

global $conf, $db;

// Retrieve the request's body and parse it as JSON
$input = @file_get_contents("php://input");
$event = json_decode($input);

// Log webhook receipt
dol_syslog("Paystack Webhook: Received event - " . $input, LOG_DEBUG);

// Verify webhook signature
$test_mode = getDolGlobalInt('PAYSTACK_TEST_MODE', 1);
$secret_key = $test_mode ? getDolGlobalString('PAYSTACK_TEST_SECRET_KEY') : getDolGlobalString('PAYSTACK_LIVE_SECRET_KEY');

if (empty($secret_key)) {
    http_response_code(500);
    dol_syslog("Paystack Webhook: Secret key not configured", LOG_ERR);
    exit;
}

// Verify that the signature matches
if (!isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'])) {
    http_response_code(400);
    dol_syslog("Paystack Webhook: Missing signature header", LOG_WARNING);
    exit;
}

$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'];
$computed_signature = hash_hmac('sha512', $input, $secret_key);

if ($signature !== $computed_signature) {
    http_response_code(401);
    dol_syslog("Paystack Webhook: Invalid signature", LOG_WARNING);
    exit;
}

// Process the event
if ($event && isset($event->event)) {
    dol_syslog("Paystack Webhook: Processing event type - " . $event->event, LOG_DEBUG);
    
    switch ($event->event) {
        case 'charge.success':
            // Payment was successful
            if (isset($event->data->reference)) {
                $reference = $event->data->reference;
                $amount = $event->data->amount / 100; // Convert from kobo/pesewas to main unit
                $status = $event->data->status;
                
                dol_syslog("Paystack Webhook: Payment successful - Reference: $reference, Amount: $amount, Status: $status", LOG_INFO);
                
                // Here you could add additional processing if needed
                // For example, sending notifications, updating custom tables, etc.
                
                http_response_code(200);
            }
            break;
            
        case 'charge.failed':
            // Payment failed
            if (isset($event->data->reference)) {
                $reference = $event->data->reference;
                dol_syslog("Paystack Webhook: Payment failed - Reference: $reference", LOG_WARNING);
                
                http_response_code(200);
            }
            break;
            
        default:
            // Unhandled event type
            dol_syslog("Paystack Webhook: Unhandled event type - " . $event->event, LOG_DEBUG);
            http_response_code(200);
            break;
    }
} else {
    http_response_code(400);
    dol_syslog("Paystack Webhook: Invalid event data", LOG_WARNING);
}

$db->close();
