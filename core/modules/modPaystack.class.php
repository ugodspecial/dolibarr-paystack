<?php
/* Copyright (C) 2024 Paystack Payment Gateway Module
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    core/modules/modPaystack.class.php
 * \ingroup paystack
 * \brief   Paystack module descriptor - External payment gateway
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Paystack module descriptor
 * External module to interface with Paystack payment system
 */
class modPaystack extends DolibarrModules
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        
        // Module unique ID (500000-599999 range for external modules)
        $this->numero = 500000;
        
        // Module key for permissions, menus, etc.
        $this->rights_class = 'paystack';
        
        // Module family: 'financial' = Payment/Banking modules
        $this->family = "financial";
        
        // Module position in the family
        $this->module_position = '90';
        
        // Module name
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        
        // Module description
        $this->description = "Paystack Payment Gateway - Accept payments in Africa";
        
        // Module version
        $this->version = '2.0.0';
        
        // Module constant name
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        
        // Module icon
        $this->picto = 'paystack@paystack';
        
        // Module parts - hooks for payment integration
        $this->module_parts = array(
            // 'newpayment' - getValidPayment, doAddButton, doPayment hooks on payment form
            // 'paymentok'  - isPaymentOK hook called by paymentok.php after redirect back
            // 'paystack'   - module-specific context (keep for backward compat)
            'hooks' => array('newpayment', 'paymentok', 'paystack')
        );
        
        // Data directories
        $this->dirs = array();
        
        // Config pages
        $this->config_page_url = array("setup.php@paystack");
        
        // Module dependencies
        $this->hidden = false;
        $this->depends = array();  // No dependencies
        $this->requiredby = array();
        $this->conflictwith = array();
        
        // PHP and Dolibarr minimum versions
        $this->phpmin = array(7, 0);
        $this->need_dolibarr_version = array(13, 0);
        
        // Constants - empty array
        $this->const = array();
        
        // Tabs - empty array
        $this->tabs = array();
        
        // Dictionaries - empty array
        $this->dictionaries = array();
        
        // Boxes - empty array
        $this->boxes = array();
        
        // CRITICAL: NO RIGHTS/PERMISSIONS
        // External payment gateway modules don't need user permissions
        // They interface with external systems (like Stripe, PayPal, HelloAsso)
        $this->rights = array();  // EMPTY - no permissions needed!
        
        // Menus - empty array
        $this->menu = array();
        
        // Exports - empty array
        $this->export_code = array();
        $this->export_label = array();
    }

    /**
     * Function called when module is enabled
     *
     * @param  string $options Options
     * @return int             1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $sql = array();
        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled
     *
     * @param  string $options Options
     * @return int             1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
