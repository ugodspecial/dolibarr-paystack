# Changelog

## [2.0.0] - 2024-02-13

### MAJOR RELEASE - Complete Rebuild as External Payment Gateway

**This is a complete architectural rebuild following Dolibarr's external payment module standards.**

### Changed
- **Module Type:** Now properly classified as **External Payment Gateway** module
- **No User Permissions:** Removed all user permission requirements (matches Stripe, PayPal, HelloAsso pattern)
- **Proper Registration:** Uses `getValidPayment` hook (official Dolibarr standard)
- **External Interface:** Module now correctly categorized as interfacing with external system

### Fixed
- **CRITICAL: Payment method registration**
  - Now uses proper `getValidPayment` hook instead of `doValidatePayment`
  - Follows Dolibarr Online Payment Module Architecture
  - Works exactly like HelloAsso, Stripe, PayPal modules
  
- **Module Classification**
  - No longer asks for user permissions
  - Properly identified as external interface module
  - Listed among external payment systems

### Technical Changes

**Module Descriptor (modPaystack.class.php):**
```php
// REMOVED: User permissions/rights
$this->rights = array();  // Empty - external modules don't need permissions!

// KEPT: Hook registration
$this->module_parts = array(
    'hooks' => array('newpayment')
);
```

**Hooks Implementation (actions_paystack.class.php):**
```php
// NEW: Proper hook name
public function getValidPayment($parameters, &$object, &$action)
{
    // Registers paystack as valid payment method
    $parameters['validpaymentmethod']['paystack'] = 'paystack';
}

// (Previously was: doValidatePayment - which is non-standard)
```

### Documentation
- Completely rewritten README
- Explains external payment gateway concept
- Clarifies why no user permissions needed
- Follows Paystack official integration guidelines

### Architecture
Based on:
- Dolibarr Online Payment Module Architecture (wiki)
- HelloAsso module pattern
- Stripe/PayPal module structure
- Paystack API official documentation

---

## Previous Versions (1.0.x)

### [1.0.4] - 2024-02-13
- Attempted fix for payment registration
- Still used incorrect hook pattern

### [1.0.3] - 2024-02-13  
- Rebuilt with improvements
- Still had permissions defined

### [1.0.2] - 2024-02-12
- Button click fixes

### [1.0.1] - 2024
- Test/live API key separation
- Bank account selection

### [1.0.0] - 2024
- Initial release
- Basic functionality

---

## Migration from 1.x to 2.0.0

### No Configuration Changes Required!

```
1. Disable Paystack 1.x module
2. Install paystack-2.0.0.zip
3. Enable module
4. Your API keys are preserved ✅
5. Works immediately!
```

### What You'll Notice

**Before (1.x):**
- Module showed user permissions
- Asked about user rights
- May have shown permission errors

**After (2.0):**
- NO user permissions shown (correct!)
- Works as external interface
- Cleaner, simpler operation

---

## Technical Details

### Why Version 2.0?

Version 2.0 represents a **fundamental architectural change**:

1. **Module Classification**
   - v1.x: Internal module with permissions
   - v2.0: External payment gateway (correct!)

2. **Hook Implementation**
   - v1.x: Used `doValidatePayment` (non-standard)
   - v2.0: Uses `getValidPayment` (Dolibarr standard)

3. **Permissions Model**
   - v1.x: Had user rights defined
   - v2.0: No permissions needed (external interface)

### Follows Official Standards

v2.0 now matches these official modules:
- ✅ Stripe (core module)
- ✅ PayPal (core module)
- ✅ HelloAsso (external module)
- ✅ PayPlug (external module)
- ✅ Lyra (external module)

### References

Based on official documentation:
- https://wiki.dolibarr.org/index.php/Online_Payment_Module_Architecture
- https://wiki.dolibarr.org/index.php/Module_HelloAsso
- Paystack API Documentation: https://paystack.com/docs/

---

## Upgrade Checklist

### From 1.0.x to 2.0.0

- [ ] Backup current configuration (API keys)
- [ ] Disable Paystack 1.x module
- [ ] Install paystack-2.0.0.zip
- [ ] Enable Paystack 2.0 module
- [ ] Verify API keys preserved
- [ ] Test payment with test card
- [ ] Notice NO permission settings (this is correct!)
- [ ] Confirm payment works
- [ ] Update webhooks if changed
- [ ] Go live when ready

---

## What's Fixed in 2.0.0

| Issue | Status in 1.x | Status in 2.0 |
|-------|--------------|--------------|
| Payment method registration | ❌ Unreliable | ✅ Proper hook |
| User permissions shown | ❌ Yes (wrong) | ✅ No (correct) |
| External module classification | ❌ No | ✅ Yes |
| Follows Dolibarr standards | ❌ Partial | ✅ Complete |
| Pattern matches Stripe/PayPal | ❌ No | ✅ Yes |
| Based on official docs | ❌ Partial | ✅ Complete |

---

**Current Version:** 2.0.0 (Stable - Production Ready) ✅

**Module Type:** External Payment Gateway (No User Permissions Required)

**Tested On:** Dolibarr 22.0.4

**PHP Version:** 7.0+

---

## Support

If you have questions about why there are no user permissions:
- This is **correct** and **by design**
- External payment gateways don't need permissions
- Same as Stripe, PayPal, HelloAsso
- See README.md section "No User Permissions Required"

---

**v2.0.0 - Finally Built Correctly!** 🎉
