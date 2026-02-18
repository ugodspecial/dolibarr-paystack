<?php
/* Copyright (C) 2024 Paystack Payment Gateway Module
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    admin/setup.php
 * \ingroup paystack
 * \brief   Paystack module setup page
 */

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
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/paystack.lib.php';

// Translations
$langs->loadLangs(array("admin", "paystack@paystack", "banks", "errors"));

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * Actions
 */

if ($action == 'update') {
    // Test mode
    $test_mode = GETPOST('PAYSTACK_TEST_MODE', 'int');
    dolibarr_set_const($db, "PAYSTACK_TEST_MODE", $test_mode, 'int', 0, '', $conf->entity);
    
    // Test keys - MATCHING names from actions_paystack.class.php
    $test_public_key = GETPOST('PAYSTACK_TEST_PUBLIC_KEY', 'alpha');
    $test_secret_key = GETPOST('PAYSTACK_TEST_SECRET_KEY', 'alpha');
    dolibarr_set_const($db, "PAYSTACK_TEST_PUBLIC_KEY", $test_public_key, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, "PAYSTACK_TEST_SECRET_KEY", $test_secret_key, 'chaine', 0, '', $conf->entity);
    
    // Live keys - MATCHING names from actions_paystack.class.php
    $live_public_key = GETPOST('PAYSTACK_LIVE_PUBLIC_KEY', 'alpha');
    $live_secret_key = GETPOST('PAYSTACK_LIVE_SECRET_KEY', 'alpha');
    dolibarr_set_const($db, "PAYSTACK_LIVE_PUBLIC_KEY", $live_public_key, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, "PAYSTACK_LIVE_SECRET_KEY", $live_secret_key, 'chaine', 0, '', $conf->entity);
    
    // Currency
    $currency = GETPOST('PAYSTACK_CURRENCY', 'alpha');
    dolibarr_set_const($db, "PAYSTACK_CURRENCY", $currency, 'chaine', 0, '', $conf->entity);
    
    // Bank account
    $bank_account = GETPOST('PAYSTACK_BANK_ACCOUNT_FOR_PAYMENTS', 'int');
    dolibarr_set_const($db, "PAYSTACK_BANK_ACCOUNT_FOR_PAYMENTS", $bank_account, 'int', 0, '', $conf->entity);
    
    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

/*
 * View
 */

$page_name = "PaystackSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = paystackAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans("ModuleSetup"), -1, 'paystack@paystack');

print '<span class="opacitymedium">'.$langs->trans("PaystackSetupDesc").'</span><br><br>';

// Setup form
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameter") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '</tr>';

// Test Mode
$test_mode = getDolGlobalInt('PAYSTACK_TEST_MODE', 1);
print '<tr class="oddeven">';
print '<td class="titlefield">';
print '<span class="fieldrequired">'.$langs->trans("PaystackTestMode").'</span>';
print '</td>';
print '<td>';
print '<input type="checkbox" name="PAYSTACK_TEST_MODE" value="1"' . ($test_mode ? ' checked' : '') . '> ';
print '<span class="opacitymedium">'.$langs->trans("PaystackTestModeDesc").'</span>';
print '</td>';
print '</tr>';

print '</table>';

// Test API Keys Section
print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans("PaystackTestAPIKeys") . '</td>';
print '</tr>';

// Test Public Key
print '<tr class="oddeven">';
print '<td class="titlefield">';
print '<span' . ($test_mode ? ' class="fieldrequired"' : '') . '>'.$langs->trans("PaystackPublicKey").'</span>';
print '</td>';
print '<td>';
print '<input type="text" class="flat minwidth500" name="PAYSTACK_TEST_PUBLIC_KEY" value="' . getDolGlobalString('PAYSTACK_TEST_PUBLIC_KEY') . '" placeholder="pk_test_xxxxxxxxxxxxx">';
print '</td>';
print '</tr>';

// Test Secret Key
print '<tr class="oddeven">';
print '<td>';
print '<span' . ($test_mode ? ' class="fieldrequired"' : '') . '>'.$langs->trans("PaystackSecretKey").'</span>';
print '</td>';
print '<td>';
print '<input type="password" class="flat minwidth500" name="PAYSTACK_TEST_SECRET_KEY" value="' . getDolGlobalString('PAYSTACK_TEST_SECRET_KEY') . '" autocomplete="new-password" placeholder="sk_test_xxxxxxxxxxxxx">';
print '</td>';
print '</tr>';

print '</table>';

// Live API Keys Section
print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans("PaystackLiveAPIKeys") . '</td>';
print '</tr>';

// Live Public Key
print '<tr class="oddeven">';
print '<td class="titlefield">';
print '<span' . (!$test_mode ? ' class="fieldrequired"' : '') . '>'.$langs->trans("PaystackPublicKey").'</span>';
print '</td>';
print '<td>';
print '<input type="text" class="flat minwidth500" name="PAYSTACK_LIVE_PUBLIC_KEY" value="' . getDolGlobalString('PAYSTACK_LIVE_PUBLIC_KEY') . '" placeholder="pk_live_xxxxxxxxxxxxx">';
print '</td>';
print '</tr>';

// Live Secret Key
print '<tr class="oddeven">';
print '<td>';
print '<span' . (!$test_mode ? ' class="fieldrequired"' : '') . '>'.$langs->trans("PaystackSecretKey").'</span>';
print '</td>';
print '<td>';
print '<input type="password" class="flat minwidth500" name="PAYSTACK_LIVE_SECRET_KEY" value="' . getDolGlobalString('PAYSTACK_LIVE_SECRET_KEY') . '" autocomplete="new-password" placeholder="sk_live_xxxxxxxxxxxxx">';
print '</td>';
print '</tr>';

print '</table>';

// Other Settings
print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans("OtherSettings") . '</td>';
print '</tr>';

// Currency
$currency = getDolGlobalString('PAYSTACK_CURRENCY', 'NGN');
print '<tr class="oddeven">';
print '<td class="titlefield">';
print '<span class="fieldrequired">'.$langs->trans("PaystackCurrency").'</span>';
print '</td>';
print '<td>';
print '<select name="PAYSTACK_CURRENCY" class="flat">';
print '<option value="NGN"' . ($currency == 'NGN' ? ' selected' : '') . '>NGN - Nigerian Naira</option>';
print '<option value="GHS"' . ($currency == 'GHS' ? ' selected' : '') . '>GHS - Ghanaian Cedi</option>';
print '<option value="ZAR"' . ($currency == 'ZAR' ? ' selected' : '') . '>ZAR - South African Rand</option>';
print '<option value="USD"' . ($currency == 'USD' ? ' selected' : '') . '>USD - US Dollar</option>';
print '<option value="KES"' . ($currency == 'KES' ? ' selected' : '') . '>KES - Kenyan Shilling</option>';
print '</select>';
print '<br><span class="opacitymedium">'.$langs->trans("PaystackCurrencyDesc").'</span>';
print '</td>';
print '</tr>';

// Bank Account Selection
if (isModEnabled('bank')) {
    $bankaccountid = getDolGlobalInt('PAYSTACK_BANK_ACCOUNT_FOR_PAYMENTS');
    print '<tr class="oddeven">';
    print '<td>';
    print '<span>'.$langs->trans("PaystackBankAccount").'</span>';
    print '</td>';
    print '<td>';
    
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
    $form = new Form($db);
    $form->select_comptes($bankaccountid, 'PAYSTACK_BANK_ACCOUNT_FOR_PAYMENTS', 0, '', 1);
    
    print '<br><span class="opacitymedium">'.$langs->trans("PaystackBankAccountDesc").'</span>';
    print '</td>';
    print '</tr>';
} else {
    print '<tr class="oddeven">';
    print '<td colspan="2">';
    print '<div class="warning">'.$langs->trans("PaystackBankModuleNotEnabled").'</div>';
    print '</td>';
    print '</tr>';
}

print '</table>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// Webhook Configuration Section
print '<br><br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">Webhook Configuration (Optional - For Real-Time Notifications)</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="titlefield">';
print $langs->trans("PaystackWebhookURL");
print '</td>';
print '<td>';
$webhook_url = dol_buildpath('/custom/paystack/webhook.php', 2);
print '<input type="text" class="flat minwidth500" value="' . $webhook_url . '" readonly onclick="this.select();">';
print ' <button type="button" class="button" onclick="navigator.clipboard.writeText(\''.$webhook_url.'\'); alert(\'Copied to clipboard!\');">'.$langs->trans("Copy").'</button>';
print '<br><span class="opacitymedium">'.$langs->trans("PaystackWebhookDesc").'</span>';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td colspan="2">';
print '<div class="info">';
print '<strong>'.$langs->trans("PaystackWebhookSteps").':</strong><br>';
print '1. '.$langs->trans("PaystackWebhookStep1").'<br>';
print '2. '.$langs->trans("PaystackWebhookStep2").' <code>'.htmlentities($webhook_url).'</code><br>';
print '3. '.$langs->trans("PaystackWebhookStep3").'<br>';
print '&nbsp;&nbsp;&nbsp;☑ charge.success<br>';
print '&nbsp;&nbsp;&nbsp;☑ charge.failed<br>';
print '4. '.$langs->trans("PaystackWebhookStep4").'<br>';
print '</div>';
print '</td>';
print '</tr>';

print '</table>';

// Information Section
print '<br><br>';
print '<div class="info">';
print '<strong>'.$langs->trans("PaystackGetKeys").':</strong><br>';
print '1. '.$langs->trans("PaystackSignup").' <a href="https://paystack.com" target="_blank">https://paystack.com</a><br>';
print '2. '.$langs->trans("PaystackLogin").'<br>';
print '3. '.$langs->trans("PaystackGoSettings").'<br>';
print '4. '.$langs->trans("PaystackCopyKeys").'<br>';
print '<br>';
print '<strong>'.$langs->trans("PaystackTestKeys").':</strong><br>';
print $langs->trans("PaystackTestKeysDesc");
print '</div>';

// End of page
print dol_get_fiche_end();

llxFooter();
$db->close();
