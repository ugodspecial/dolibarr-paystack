<?php
/* Copyright (C) 2024 Paystack Payment Gateway Module
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    class/actions_paystack.class.php
 * \ingroup paystack
 * \brief   Paystack hooks - Following Dolibarr Online Payment Module Architecture
 * \see     https://wiki.dolibarr.org/index.php/Online_Payment_Module_Architecture
 */

/**
 * Class ActionsPaystack
 */
class ActionsPaystack
{
    /** @var DoliDB Database handler */
    public $db;
    /** @var string Error */
    public $error = '';
    /** @var array Errors */
    public $errors = array();
    /** @var array Hook results */
    public $results = array();
    /** @var string Resprints */
    public $resprints;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * getValidPayment hook
     * Called by newpayment.php via getValidOnlinePaymentMethods()
     * This registers Paystack as a valid payment method
     *
     * @param array   $parameters Hook parameters
     * @param object  $object     Object
     * @param string  $action     Action
     * @return int                0
     */
    public function getValidPayment($parameters, &$object, &$action)
    {
        global $conf;

        // Only add if module is enabled
        if (!empty($conf->paystack->enabled)) {
            // Register paystack in the validpaymentmethod array
            if (isset($parameters['validpaymentmethod'])) {
                $parameters['validpaymentmethod']['paystack'] = 'paystack';
            }
            
            dol_syslog("Paystack: Registered as valid payment method", LOG_DEBUG);
        }

        return 0;
    }

    /**
     * doAddButton hook
     * Add the payment button on newpayment.php
     *
     * @param array   $parameters Hook parameters
     * @param object  $object     Object
     * @param string  $action     Action
     * @return int                0
     */
    public function doAddButton($parameters, &$object, &$action)
    {
        global $conf, $langs;

        if (empty($conf->paystack->enabled)) {
            return 0;
        }

        $langs->load("paystack@paystack");
        
        $paymentmethod = isset($parameters['paymentmethod']) ? $parameters['paymentmethod'] : '';
        
        // Show button if paystack is selected or no method selected
        if ($paymentmethod == 'paystack' || empty($paymentmethod)) {
            $this->resprints = '
<div class="center opacitymedium" style="margin: 20px 0;">
    <form method="POST" action="'.$_SERVER['PHP_SELF'].'" id="paystack-form">
        <input type="hidden" name="token" value="'.newToken().'">
        <input type="hidden" name="action" value="dopayment">
        <input type="hidden" name="paymentmethod" value="paystack">';
        
            // Preserve all parameters
            foreach ($_GET as $key => $val) {
                if ($key != 'action' && $key != 'paymentmethod' && $key != 'token') {
                    $this->resprints .= '<input type="hidden" name="'.dol_escape_htmltag($key).'" value="'.dol_escape_htmltag($val).'">';
                }
            }
            
            foreach ($_POST as $key => $val) {
                if ($key != 'action' && $key != 'paymentmethod' && $key != 'token') {
                    $this->resprints .= '<input type="hidden" name="'.dol_escape_htmltag($key).'" value="'.dol_escape_htmltag($val).'">';
                }
            }
            
            $this->resprints .= '
        <button type="submit" class="butAction" name="paystack-btn" style="background: linear-gradient(135deg, #00C9A7 0%, #00A0DB 100%); color: white; padding: 12px 40px; font-size: 16px; font-weight: 500; border-radius: 6px; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(0,201,167,0.3); transition: all 0.3s ease;">
            <span class="fa fa-credit-card" style="margin-right: 8px;"></span>
            <span>'.$langs->trans("PayWithPaystack").'</span>
        </button>
    </form>
</div>';
        }

        return 0;
    }

    /**
     * doPayment hook
     * Process the payment - initialize Paystack transaction and redirect
     *
     * @param array   $parameters Hook parameters
     * @param object  $object     Object
     * @param string  $action     Action
     * @return int                0 or -1
     */
    public function doPayment($parameters, &$object, &$action)
    {
        global $conf, $langs;

        if (empty($conf->paystack->enabled)) {
            return 0;
        }

        $paymentmethod = isset($parameters['paymentmethod']) ? $parameters['paymentmethod'] : '';
        
        if ($paymentmethod != 'paystack') {
            return 0;
        }

        dol_syslog("Paystack: doPayment called", LOG_INFO);
        dol_syslog("Paystack: parameters = " . print_r($parameters, true), LOG_DEBUG);
        dol_syslog("Paystack: GET = " . print_r($_GET, true), LOG_DEBUG);
        dol_syslog("Paystack: POST amount = " . GETPOST('amount', 'int'), LOG_DEBUG);
        
        $langs->load("paystack@paystack");
        
        // Get API keys
        $test_mode = getDolGlobalInt('PAYSTACK_TEST_MODE', 1);
        $secret_key = $test_mode ? getDolGlobalString('PAYSTACK_TEST_SECRET_KEY') : getDolGlobalString('PAYSTACK_LIVE_SECRET_KEY');
        $public_key = $test_mode ? getDolGlobalString('PAYSTACK_TEST_PUBLIC_KEY') : getDolGlobalString('PAYSTACK_LIVE_PUBLIC_KEY');
        
        if (empty($secret_key) || empty($public_key)) {
            setEventMessages($langs->trans("PaystackNotConfigured"), null, 'errors');
            dol_syslog("Paystack: API keys not configured", LOG_ERR);
            return -1;
        }
        
        // Get payment details
        // Amount can come from parameters or POST data
        $amount = 0;
        if (isset($parameters['amount']) && $parameters['amount'] > 0) {
            $amount = $parameters['amount'];
        } else {
            $amount = GETPOST('amount', 'int');
        }
        
        // Get tag - CRITICAL: Try multiple sources (lowercase first as Dolibarr uses lowercase)
        $tag = '';
        if (isset($parameters['tag']) && !empty($parameters['tag'])) {
            $tag = $parameters['tag'];
        }
        if (empty($tag)) {
            $tag = GETPOST('fulltag', 'alpha');  // Try lowercase first (Dolibarr standard)
        }
        if (empty($tag)) {
            $tag = GETPOST('FULLTAG', 'alpha');  // Try uppercase (backward compatibility)
        }
        if (empty($tag)) {
            $tag = GETPOST('tag', 'alpha');
        }
        if (empty($tag)) {
            $tag = GETPOST('ref', 'alpha');
        }
        
        // CRITICAL: Transform tag for URL - replace = with - to pass Dolibarr's 'alpha' filter!
        // INV=2.CUS=1 becomes INV-2.CUS-1
        // This is required because Dolibarr's GETPOST('fulltag', 'alpha') strips = signs
        $tag_for_url = str_replace('=', '-', $tag);
        
        // Store original tag in session for verification
        $_SESSION['PAYSTACK_ORIGINAL_TAG'] = $tag;
        $_SESSION['PAYSTACK_URL_TAG'] = $tag_for_url;
        
        dol_syslog("Paystack: Original tag: $tag, URL-safe tag: $tag_for_url", LOG_DEBUG);
        
        // Get customer email and name
        $email = GETPOST('email', 'alpha');
        $firstname = GETPOST('firstname', 'alpha');
        $lastname = GETPOST('lastname', 'alpha');
        
        dol_syslog("Paystack: Payment data - Amount: $amount, Original Tag: $tag, URL Tag: $tag_for_url, Email: $email", LOG_DEBUG);
        
        // Validate tag
        if (empty($tag) || empty($tag_for_url)) {
            setEventMessages("Payment reference (FULLTAG) is missing. Cannot process payment.", null, 'errors');
            dol_syslog("Paystack: FULLTAG/tag is empty - Cannot process", LOG_ERR);
            return -1;
        }
        
        // Validate amount
        if (empty($amount) || $amount <= 0) {
            setEventMessages("Invalid payment amount: " . $amount . ". Please check the invoice.", null, 'errors');
            dol_syslog("Paystack: Invalid amount - " . $amount, LOG_ERR);
            return -1;
        }
        
        // Validate email
        if (empty($email)) {
            setEventMessages($langs->trans("EmailRequired"), null, 'errors');
            return -1;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setEventMessages("Invalid email address format", null, 'errors');
            dol_syslog("Paystack: Invalid email - " . $email, LOG_ERR);
            return -1;
        }
        
        // Convert amount to kobo/pesewas/cents
        $currency = getDolGlobalString('PAYSTACK_CURRENCY', 'NGN');
        $amount_in_subunit = (int)($amount * 100);
        
        // Validate amount is not zero after conversion
        if ($amount_in_subunit <= 0) {
            setEventMessages("Amount too small for payment", null, 'errors');
            dol_syslog("Paystack: Amount too small - " . $amount_in_subunit, LOG_ERR);
            return -1;
        }
        
        // Generate unique reference using URL-safe tag (without = signs)
        $reference = 'DOL_' . $tag_for_url . '_' . time() . '_' . mt_rand(1000, 9999);
        
        // Build callback URL with URL-safe fulltag - Dolibarr REQUIRES this!
        $urlok = DOL_MAIN_URL_ROOT.'/public/payment/paymentok.php';
        $callback_url = $urlok.'?fulltag='.urlencode($tag_for_url);  // Use URL-safe tag!
        
        dol_syslog("Paystack: Callback URL with URL-safe fulltag - ".$callback_url, LOG_DEBUG);
        
        dol_syslog("Paystack: Initializing transaction - Ref: $reference, Amount: $amount $currency", LOG_INFO);
        dol_syslog("Paystack: Callback URL (with fulltag) - " . $callback_url, LOG_DEBUG);
        
        // Initialize transaction with Paystack API
        $api_url = 'https://api.paystack.co/transaction/initialize';
        
        $data = array(
            'email' => $email,
            'amount' => $amount_in_subunit,
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $callback_url,  // Include callback with fulltag!
            'metadata' => array(
                'custom_fields' => array(
                    array(
                        'display_name' => 'Customer Name',
                        'variable_name' => 'customer_name',
                        'value' => trim($firstname . ' ' . $lastname)
                    ),
                    array(
                        'display_name' => 'Invoice/Tag',
                        'variable_name' => 'tag',
                        'value' => $tag  // Use original tag for display
                    ),
                    array(
                        'display_name' => 'Reference',
                        'variable_name' => 'reference',
                        'value' => $reference
                    )
                )
            )
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $secret_key,
            'Content-Type: application/json'
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Log the full request and response for debugging
        dol_syslog("Paystack: API Request - URL: $api_url", LOG_DEBUG);
        dol_syslog("Paystack: API Request - Data: " . json_encode($data), LOG_DEBUG);
        dol_syslog("Paystack: API Response - HTTP $http_code", LOG_DEBUG);
        dol_syslog("Paystack: API Response - Body: " . substr($response, 0, 500), LOG_DEBUG);
        
        if ($http_code == 200) {
            $result = json_decode($response, true);
            
            if ($result['status'] === true && !empty($result['data']['authorization_url'])) {
                // Store in session - Dolibarr needs these for payment recording
                // CRITICAL: Store both original tag and URL-safe tag
                $_SESSION['FULLTAG'] = $tag_for_url;  // URL-safe version for Dolibarr
                $_SESSION['fulltag'] = $tag_for_url;  // lowercase version
                $_SESSION['PAYSTACK_ORIGINAL_TAG'] = $tag;  // Original with = signs
                $_SESSION['FULLTAGpaystack'] = $tag_for_url;  // payment method specific
                
                $_SESSION['TRANSACTIONID'] = $reference;
                $_SESSION['FinalPaymentAmt'] = $amount;
                $_SESSION['currencyCodeType'] = $currency;
                $_SESSION['PAYSTACK_REFERENCE'] = $reference;
                $_SESSION['paymentType'] = 'paystack';
                $_SESSION['paymentmethod'] = 'paystack';
                
                dol_syslog("Paystack: Session data stored - Original tag: $tag, URL tag: $tag_for_url, Reference: $reference, Amount: $amount", LOG_INFO);
                dol_syslog("Paystack: Redirecting to checkout - " . $result['data']['authorization_url'], LOG_INFO);
                
                // Redirect to Paystack checkout
                header('Location: ' . $result['data']['authorization_url']);
                exit;
            } else {
                $error_msg = isset($result['message']) ? $result['message'] : 'Unknown error';
                setEventMessages($langs->trans("PaystackInitializationFailed") . ': ' . $error_msg, null, 'errors');
                dol_syslog("Paystack: Initialization failed - " . $error_msg, LOG_ERR);
                dol_syslog("Paystack: Full response - " . $response, LOG_ERR);
                return -1;
            }
        } else {
            // Decode error response
            $result = json_decode($response, true);
            $error_msg = 'HTTP ' . $http_code;
            
            if (isset($result['message'])) {
                $error_msg .= ': ' . $result['message'];
            }
            
            if (!empty($curl_error)) {
                $error_msg .= ' - cURL: ' . $curl_error;
            }
            
            setEventMessages($langs->trans("PaystackAPIError") . ': ' . $error_msg, null, 'errors');
            dol_syslog("Paystack: API error - " . $error_msg, LOG_ERR);
            dol_syslog("Paystack: Full response - " . $response, LOG_ERR);
            return -1;
        }

        return 0;
    }

    /**
     * isPaymentOK hook  
     * Verify payment after redirect from Paystack
     *
     * @param array   $parameters Hook parameters
     * @param object  $object     Object
     * @param string  $action     Action
     * @return int                0
     */
    public function isPaymentOK($parameters, &$object, &$action)
    {
        global $conf;

        if (empty($conf->paystack->enabled)) {
            return 0;
        }

        dol_syslog("Paystack: isPaymentOK hook called", LOG_DEBUG);
        dol_syslog("Paystack: GET params = ".print_r($_GET, true), LOG_DEBUG);
        
        $reference = GETPOST('reference', 'alpha');
        
        if (empty($reference) && isset($_SESSION['PAYSTACK_REFERENCE'])) {
            $reference = $_SESSION['PAYSTACK_REFERENCE'];
        }
        
        if (empty($reference) && isset($_SESSION['TRANSACTIONID'])) {
            $reference = $_SESSION['TRANSACTIONID'];
        }
        
        dol_syslog("Paystack: Reference to verify: ".$reference, LOG_DEBUG);
        
        if (!empty($reference) && strpos($reference, 'DOL_') === 0) {
            // Get API key
            $test_mode = getDolGlobalInt('PAYSTACK_TEST_MODE', 1);
            $secret_key = $test_mode ? getDolGlobalString('PAYSTACK_TEST_SECRET_KEY') : getDolGlobalString('PAYSTACK_LIVE_SECRET_KEY');
            
            if (empty($secret_key)) {
                dol_syslog("Paystack: No secret key for verification", LOG_ERR);
                return 0;
            }
            
            dol_syslog("Paystack: Verifying transaction with Paystack API", LOG_INFO);
            
            // Verify transaction
            $api_url = 'https://api.paystack.co/transaction/verify/' . urlencode($reference);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $secret_key
            ));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            dol_syslog("Paystack: Verification API response - HTTP ".$http_code, LOG_DEBUG);
            dol_syslog("Paystack: Response body - ".substr($response, 0, 500), LOG_DEBUG);
            
            if ($http_code == 200) {
                $result = json_decode($response, true);
                
                if ($result['status'] === true && 
                    isset($result['data']['status']) && 
                    $result['data']['status'] === 'success') {
                    
                    // Payment verified successfully!
                    $amount = $result['data']['amount'] / 100;
                    $currency = isset($result['data']['currency']) ? $result['data']['currency'] : 'NGN';
                    
                    dol_syslog("Paystack: Payment SUCCESSFUL - Ref: ".$reference.", Amount: ".$amount." ".$currency, LOG_INFO);
                    
                    // Set result
                    $this->results['ispaymentok'] = true;
                    
                    // Update session variables - CRITICAL for Dolibarr
                    $_SESSION['TRANSACTIONID'] = $reference;
                    $_SESSION['FinalPaymentAmt'] = $amount;
                    $_SESSION['currencyCodeType'] = $currency;
                    $_SESSION['PAYMENTMETHOD'] = 'paystack';
                    
                    dol_syslog("Paystack: Session variables set - Payment will be recorded", LOG_INFO);
                    
                    return 0;
                } else {
                    $status = isset($result['data']['status']) ? $result['data']['status'] : 'unknown';
                    dol_syslog("Paystack: Payment status not success - Status: ".$status, LOG_WARNING);
                }
            } else {
                dol_syslog("Paystack: Verification API failed - HTTP ".$http_code, LOG_ERR);
            }
        } else {
            dol_syslog("Paystack: No valid Paystack reference found", LOG_DEBUG);
        }


        return 0;
    }
}
