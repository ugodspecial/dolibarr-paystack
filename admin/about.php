<?php
/* Copyright (C) 2024 Paystack Module for Dolibarr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    admin/about.php
 * \ingroup paystack
 * \brief   About page for Paystack module
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/paystack.lib.php';

global $langs, $user;

$langs->loadLangs(array("admin", "paystack@paystack"));

if (!$user->admin) {
    accessforbidden();
}

$page_name = "PaystackAbout";

llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = paystackAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans("ModuleSetup"), -1, 'paystack@paystack');

print '<div class="fichecenter">';
print '<div class="fichethirdleft">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th class="titlefield">'.$langs->trans("Parameter").'</th>';
print '<th>'.$langs->trans("Value").'</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Version").'</td>';
print '<td>1.0.0</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("License").'</td>';
print '<td>GPL v3.0 or later</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Publisher").'</td>';
print '<td>Paystack Module for Dolibarr</td>';
print '</tr>';

print '</table>';

print '</div>';

print '<div class="fichetwothirdright">';
print '<div class="underbanner clearboth"></div>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Description").'</th>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>';
print '<p>The Paystack module integrates the Paystack payment gateway into Dolibarr, enabling you to accept online payments for invoices, donations, subscriptions, and more.</p>';

print '<p><strong>Key Features:</strong></p>';
print '<ul>';
print '<li>Accept payments via multiple channels (cards, bank transfers, USSD, mobile money)</li>';
print '<li>Support for multiple currencies (NGN, GHS, ZAR, USD)</li>';
print '<li>Test mode for safe development and testing</li>';
print '<li>Automatic payment verification and recording</li>';
print '<li>Secure API integration with HTTPS</li>';
print '<li>Seamless integration with Dolibarr payment workflow</li>';
print '</ul>';

print '<p><strong>Supported Payment Methods:</strong></p>';
print '<ul>';
print '<li>Credit/Debit Cards (Visa, Mastercard, Verve)</li>';
print '<li>Bank Transfers</li>';
print '<li>USSD</li>';
print '<li>Mobile Money</li>';
print '<li>QR Code</li>';
print '<li>Apple Pay</li>';
print '</ul>';

print '<p><strong>Resources:</strong></p>';
print '<ul>';
print '<li><a href="https://paystack.com" target="_blank">Paystack Website</a></li>';
print '<li><a href="https://paystack.com/docs/" target="_blank">Paystack Documentation</a></li>';
print '<li><a href="https://dashboard.paystack.com" target="_blank">Paystack Dashboard</a></li>';
print '<li><a href="https://support.paystack.com" target="_blank">Paystack Support</a></li>';
print '</ul>';

print '</td>';
print '</tr>';

print '</table>';

print '</div>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
