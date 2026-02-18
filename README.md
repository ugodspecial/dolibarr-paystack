# Paystack Payment Gateway - External Module for Dolibarr

## Version 2.0.0 - Production Ready

**External Payment Gateway Module** - Interfaces with Paystack payment system (like Stripe, PayPal, HelloAsso)

---

## What is This Module?

Paystack is an **external payment gateway module** that interfaces with the Paystack payment system. It allows Dolibarr to accept online payments for invoices, donations, orders, and subscriptions using Paystack's secure payment platform.

### Key Point: No User Permissions Required

This module does NOT require user permissions because it **interfaces with an external system** (Paystack API), similar to:
- Stripe module
- PayPal module
- HelloAsso module
- PayPlug module

You won't see permission settings for users - this is correct and by design!

---

## Features

### Payment Methods (via Paystack)
- 💳 Credit/Debit Cards (Visa, Mastercard, Verve)
- 🏦 Bank Transfers
- 📱 USSD
- 💰 Mobile Money (MTN, Airtel, Vodafone, etc.)
- 🔲 QR Code
- 🍎 Apple Pay

### Supported Currencies
- **NGN** - Nigerian Naira
- **GHS** - Ghanaian Cedi
- **ZAR** - South African Rand
- **USD** - US Dollar
- **KES** - Kenyan Shilling

### Integration
- ✅ Works independently (no other payment modules required)
- ✅ Test & Live modes with separate API keys
- ✅ Bank account integration for automatic payment recording
- ✅ Webhook support for real-time notifications
- ✅ Follows Paystack's official integration guidelines
- ✅ Compatible with Dolibarr 13.0+

---

## Requirements

- **Dolibarr:** Version 13.0 or higher (tested on 22.0.4)
- **PHP:** Version 7.0 or higher
- **PHP Extensions:** cURL (for API calls)
- **Paystack Account:** Free signup at https://paystack.com
- **Optional:** Bank/Cash module (for automatic payment recording)

---

## Installation

### Step 1: Upload Module

```
1. Login to Dolibarr as administrator
2. Go to: Home → Setup → Modules/Applications
3. Scroll down to: "Deploy/Install external app/module"
4. Click "Choose File" and select: paystack-2.0.0.zip
5. Click "Upload file" then "Install"
```

### Step 2: Activate Module

```
1. In the modules list, find "Paystack"
2. Click the "Activate" button
3. Green checkmark appears = module is active ✅
```

**Note:** You will NOT see user permission options - this is correct! Paystack is an external interface module.

### Step 3: Configure API Keys

1. **Get your Paystack API keys:**
   - Go to https://paystack.com
   - Sign up or log in
   - Navigate to: Settings → API Keys & Webhooks
   - Copy your keys

2. **Enter keys in Dolibarr:**
   ```
   Paystack module → Click settings icon (⚙️)
   
   Test Mode: ☑ Enabled (for testing)
   
   Test API Keys:
   - Public Key: pk_test_xxxxxxxxxxxxx
   - Secret Key: sk_test_xxxxxxxxxxxxx
   
   Live API Keys:
   - Public Key: pk_live_xxxxxxxxxxxxx
   - Secret Key: sk_live_xxxxxxxxxxxxx
   
   Currency: NGN (or your currency)
   
   Bank Account: [Select from dropdown - optional]
   
   → Click "Save"
   ```

---

## Usage

### For Customer Invoices

```
1. Customers → Invoices → New Invoice
2. Add items → Validate invoice
3. Copy "Public payment URL" from invoice
4. Share URL with customer
5. Customer opens URL → Enters email → Clicks "Pay with Paystack"
6. Redirects to Paystack → Completes payment
7. Returns to Dolibarr → Invoice automatically marked "Paid"
```

### For Donations

```
1. Enable Donations module
2. Create donation
3. Use public payment link
4. Donor pays via Paystack
5. Donation automatically recorded
```

### For Memberships/Subscriptions

```
1. Enable Members module
2. Create member + subscription
3. Share payment link
4. Member pays
5. Subscription activated
```

---

## Testing

### Test Mode Setup

```
1. Enable test mode in Paystack settings
2. Enter test API keys (pk_test_xxx and sk_test_xxx)
3. Create test invoice
4. Use Paystack test cards
```

### Test Cards (Paystack Official)

| Card Number | CVV | PIN | Expiry | Result |
|------------|-----|-----|--------|---------|
| 5531 8866 5214 2950 | 408 | 0000 | 12/26 | Success (Verve) |
| 4084 0840 8408 4081 | 408 | 0000 | 12/26 | Success (Visa) |
| 4084 0840 8408 4084 | 408 | 0000 | 12/26 | Declined |
| 5060 6666 6666 6666 | 408 | 0000 | 12/26 | Insufficient funds |

### Quick Test Procedure

```
1. Create invoice
2. Validate invoice
3. Copy payment URL
4. Open in browser
5. Enter email address
6. Click "Pay with Paystack" button
7. Should redirect to Paystack ✅
8. Enter test card details
9. Complete payment
10. Should redirect back to Dolibarr
11. Invoice should show "Paid" ✅
```

---

## Webhook Configuration (Recommended)

Webhooks allow Paystack to notify Dolibarr of payment events in real-time.

### Setup in Dolibarr

```
1. Paystack settings → Webhook Configuration section
2. Copy the webhook URL shown
   Example: https://yourdomain.com/custom/paystack/webhook.php
```

### Setup in Paystack Dashboard

```
1. Login to: https://dashboard.paystack.com
2. Go to: Settings → API Keys & Webhooks
3. Click: "Add Webhook"
4. Paste webhook URL
5. Select events:
   ☑ charge.success
   ☑ charge.failed
6. Click "Save"
```

### Test Webhook

```
1. Make a test payment
2. Check Paystack Dashboard → Webhooks → Activity Log
3. Should see HTTP 200 response ✅
```

---

## Going Live

When ready for real payments:

```
1. Paystack settings:
   ☐ Test Mode: DISABLED
   
2. Enter LIVE API keys:
   Public: pk_live_xxxxxxxxxxxxx
   Secret: sk_live_xxxxxxxxxxxxx
   
3. Click "Save"

4. Test with SMALL real transaction first

5. Verify payment recorded correctly

6. Go full production ✅
```

---

## Architecture

### Payment Flow

```
Customer accesses payment URL
    ↓
newpayment.php loads
    ↓
getValidPayment hook → Registers Paystack
    ↓
doAddButton hook → Shows "Pay with Paystack" button
    ↓
Customer clicks button
    ↓
doPayment hook → Initializes Paystack transaction
    ↓
Redirects to Paystack checkout
    ↓
Customer completes payment
    ↓
Paystack redirects back to Dolibarr
    ↓
isPaymentOK hook → Verifies payment
    ↓
Payment recorded → Invoice marked paid
```

### Hooks Implemented

1. **getValidPayment** - Registers Paystack as valid payment method
2. **doAddButton** - Displays payment button
3. **doPayment** - Initializes transaction with Paystack API
4. **isPaymentOK** - Verifies payment after redirect

### API Integration

Follows Paystack's official recommendations:
- **Initialize:** `POST /transaction/initialize`
- **Redirect:** Customer to `authorization_url`
- **Verify:** `GET /transaction/verify/{reference}`
- **Webhook:** Signature-verified notifications

---

## Troubleshooting

### Module doesn't appear in list

```
Solution:
1. Check file extracted to: /htdocs/custom/paystack/
2. Refresh modules page
3. Check Dolibarr logs for errors
```

### "Paystack not configured" error

```
Solution:
1. Enter API keys in settings
2. Ensure test mode matches key type
3. Test keys start with pk_test_ and sk_test_
4. Live keys start with pk_live_ and sk_live_
```

### Payment button doesn't appear

```
Solution:
1. Check module is activated (green checkmark)
2. Check API keys are configured
3. Clear browser cache
4. Check browser console for errors (F12)
```

### Payment not redirecting to Paystack

```
Solution:
1. Check cURL is installed: php -m | grep curl
2. Check Dolibarr logs for errors
3. Verify API keys are correct
4. Test with different browser
```

### Payment not recording

```
Solution:
1. Check webhook is configured
2. Verify payment successful in Paystack Dashboard
3. Check Dolibarr logs
4. Ensure bank account is selected (optional)
```

---

## File Structure

```
paystack/
├── admin/
│   ├── setup.php           # Configuration page
│   └── about.php           # About page
├── class/
│   └── actions_paystack.class.php  # Hooks implementation
├── core/
│   └── modules/
│       └── modPaystack.class.php   # Module descriptor
├── img/
│   └── paystack.svg        # Module icon
├── langs/
│   └── en_US/
│       └── paystack.lang   # Translations
├── lib/
│   └── paystack.lib.php    # Helper functions
├── webhook.php             # Webhook handler
└── README.md               # This file
```

---

## Security

- ✅ HTTPS-only API communication
- ✅ Secret keys never exposed to frontend
- ✅ Payment verification before recording
- ✅ Webhook signature validation
- ✅ Transaction reference validation
- ✅ Separate test/live credentials

---

## Support

- **Paystack Documentation:** https://paystack.com/docs/
- **Paystack API Reference:** https://paystack.com/docs/api/
- **Paystack Support:** support@paystack.com
- **Dolibarr Wiki:** https://wiki.dolibarr.org/
- **Dolibarr Forum:** https://www.dolibarr.org/forum/

---

## Version History

### 2.0.0 (2024-02-13) - Current
**Complete Rebuild as External Payment Gateway:**
- ✅ NO user permissions (external interface module)
- ✅ Proper payment method registration via getValidPayment hook
- ✅ Follows Dolibarr Online Payment Module Architecture
- ✅ Pattern matching HelloAsso/Stripe/PayPal modules
- ✅ Production-ready and tested

### Previous Versions
- 1.0.x - Development versions with various fixes

---

## FAQ

**Q: Why don't I see user permissions for this module?**
A: This is an external payment gateway module (like Stripe/PayPal). It interfaces with an external system and doesn't require user permissions.

**Q: Can I use this without Stripe or PayPal?**
A: Yes! Paystack works completely independently.

**Q: Which Paystack account type do I need?**
A: Any Paystack account works. Free signup at paystack.com.

**Q: Can I use this for recurring payments?**
A: Yes, with Dolibarr's subscription module.

**Q: Does this support refunds?**
A: Refunds must be processed from Paystack Dashboard.

**Q: Can I test without real money?**
A: Yes, use test mode with test API keys and test cards.

---

## License

GNU General Public License v3.0 or later

---

## Credits

**Module Type:** External Payment Gateway  
**Interfaces With:** Paystack Payment System (paystack.com)  
**Developed For:** Dolibarr ERP/CRM Community  
**Follows:** Dolibarr Online Payment Module Architecture

---

**This module interfaces with an external payment system and requires no user permissions!** ✅

Install → Configure → Accept Payments! 🚀
