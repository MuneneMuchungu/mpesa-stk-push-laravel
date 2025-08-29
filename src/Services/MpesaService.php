<?php

namespace GrishonMunene\MpesaStk\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MpesaService
{
    private $baseUrl;
    private $consumerKey;
    private $consumerSecret;
    private $shortcode;
    private $passkey;
    private $callbackUrl;

    public function __construct()
    {
        $this->baseUrl = config('mpesa.base_url');
        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
        $this->shortcode = config('mpesa.shortcode');
        $this->passkey = config('mpesa.passkey');
        $this->callbackUrl = config('mpesa.callback_url');
    }

    /**
     * Generate access token
     */
    private function generateAccessToken()
    {
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        throw new \Exception('Failed to generate access token: ' . $response->body());
    }

    /**
     * Generate password for STK Push
     */
    private function generatePassword()
    {
        $timestamp = Carbon::now()->format('YmdHis');
        return base64_encode($this->shortcode . $this->passkey . $timestamp);
    }

    /**
     * Get timestamp
     */
    private function getTimestamp()
    {
        return Carbon::now()->format('YmdHis');
    }

    /**
     * Initiate STK Push
     */
    public function stkPush($phoneNumber, $amount, $accountReference, $transactionDesc)
    {
        try {
            $accessToken = $this->generateAccessToken();
            $password = $this->generatePassword();
            $timestamp = $this->getTimestamp();

            // Format phone number
            $phone = $this->formatPhoneNumber($phoneNumber);

            $response = Http::withToken($accessToken)
                ->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => $this->shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => $amount,
                    'PartyA' => $phone,
                    'PartyB' => $this->shortcode,
                    'PhoneNumber' => $phone,
                    'CallBackURL' => $this->callbackUrl,
                    'AccountReference' => $accountReference,
                    'TransactionDesc' => $transactionDesc,
                ]);

            Log::info('M-Pesa STK Push Response', $response->json());

            return $response->json();

        } catch (\Exception $e) {
            Log::error('M-Pesa STK Push Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Query STK Push status
     */
    public function stkQuery($checkoutRequestId)
    {
        try {
            $accessToken = $this->generateAccessToken();
            $password = $this->generatePassword();
            $timestamp = $this->getTimestamp();

            $response = Http::withToken($accessToken)
                ->post($this->baseUrl . '/mpesa/stkpushquery/v1/query', [
                    'BusinessShortCode' => $this->shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $checkoutRequestId,
                ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('M-Pesa STK Query Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Format phone number
     */
    private function formatPhoneNumber($phoneNumber)
    {
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        }

        if (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }

        return $phone;
    }
}
