<?php

namespace GrishonMunene\MpesaStk\Http\Controllers;

use App\Http\Controllers\Controller;
use GrishonMunene\MpesaStk\Services\MpesaService;
use GrishonMunene\MpesaStk\Models\MpesaTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MpesaController extends Controller
{
    private $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    /**
     * Initiate STK Push
     */
    public function stkPush(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'account_reference' => 'required|string|max:12',
            'transaction_desc' => 'required|string|max:13',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $response = $this->mpesaService->stkPush(
                $request->phone_number,
                $request->amount,
                $request->account_reference,
                $request->transaction_desc
            );

            if ($response['ResponseCode'] == '0') {
                MpesaTransaction::create([
                    'checkout_request_id' => $response['CheckoutRequestID'],
                    'merchant_request_id' => $response['MerchantRequestID'],
                    'phone_number' => $request->phone_number,
                    'amount' => $request->amount,
                    'account_reference' => $request->account_reference,
                    'transaction_desc' => $request->transaction_desc,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'STK Push initiated successfully',
                    'data' => $response
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $response['ResponseDescription'] ?? 'STK Push failed',
                'data' => $response
            ], 400);

        } catch (\Exception $e) {
            Log::error('STK Push Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing payment'
            ], 500);
        }
    }

    /**
     * Handle M-Pesa callback
     */
    public function callback(Request $request)
    {
        Log::info('M-Pesa Callback Received', $request->all());

        $callbackData = $request->all();

        if (isset($callbackData['Body']['stkCallback'])) {
            $callback = $callbackData['Body']['stkCallback'];
            $checkoutRequestId = $callback['CheckoutRequestID'];

            $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

            if ($transaction) {
                $transaction->callback_response = json_encode($callbackData);

                if ($callback['ResultCode'] == 0) {
                    $transaction->status = 'completed';

                    if (isset($callback['CallbackMetadata']['Item'])) {
                        foreach ($callback['CallbackMetadata']['Item'] as $item) {
                            if ($item['Name'] == 'MpesaReceiptNumber') {
                                $transaction->mpesa_receipt_number = $item['Value'];
                                break;
                            }
                        }
                    }
                } else {
                    $transaction->status = $callback['ResultCode'] == 1032 ? 'cancelled' : 'failed';
                }

                $transaction->save();
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * Query STK Push status
     */
    public function stkQuery($checkoutRequestId)
    {
        try {
            $response = $this->mpesaService->stkQuery($checkoutRequestId);

            return response()->json([
                'success' => true,
                'data' => $response
            ]);

        } catch (\Exception $e) {
            Log::error('STK Query Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while querying payment status'
            ], 500);
        }
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus($checkoutRequestId)
    {
        $transaction = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }
}
