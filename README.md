# M-Pesa STK Push Laravel 12 Vendor Package

A Laravel 12 package that integrates M-Pesa Daraja STK Push as a vendor, making it easy to initiate and process mobile money payments in your application.

## Features
- ✅ Easy integration with Laravel 12
- ✅ Configurable via `.env`
- ✅ Handles STK Push requests and callbacks
- ✅ Works with Safaricom M-Pesa Daraja API

---

## Installation

```bash
composer require grishonmunene/mpesa-stk

## Publish vendor
php artisan vendor:publish --provider="GrishonMunene\MpesaStk\MpesaStkServiceProvider"

## Add the following environment variables to your .env file:
MPESA_STK_CONSUMER_KEY=your_consumer_key
MPESA_STK_CONSUMER_SECRET=your_consumer_secret
MPESA_STK_PASSKEY=your_passkey
MPESA_STK_SHORTCODE=your_shortcode
MPESA_STK_CALLBACK_URL=your_callback_url
MPESA_STK_ENVIRONMENT=sandbox

