<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\MpesaSTK;
use Illuminate\Http\JsonResponse;
use App\Services\MpesaAuthService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Traits\MobileFormattingTrait;
use Illuminate\Support\Facades\Cache;


class MpesaStkService
{

    use MobileFormattingTrait;

    protected MpesaAuthService $mpesaAuthService;

    public function __construct(MpesaAuthService $mpesaAuthService)
    {
        $this->mpesaAuthService = $mpesaAuthService;
    }


    public function generateAccessToken()
    {
        $consumerKey = env('MPESA_CONSUMER_KEY');
        $consumerSecret = env('MPESA_CONSUMER_SECRET');
        $tokenUrl = env('MPESA_GENERATE_TOKEN_URL');
        
        try {
            // Use basic auth with consumer key and secret
            $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
            
            $ch = curl_init($tokenUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . $credentials,
                    'Content-Type: application/json',
                ],
            ]);
            
            $response = curl_exec($ch);
            $error = curl_error($ch);
            
            if ($error) {
                Log::error("AUTH_TOKEN_ERROR: $error");
                return [
                    'status' => 'error',
                    'message' => $error
                ];
            }
            
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if (isset($result['access_token'])) {
                Log::info("AUTH_TOKEN: Token fetched successfully");
                return [
                    'access_token' => $result['access_token'],
                    'expires_in' => $result['expires_in'] ?? 3599
                ];
            } else {
                Log::error("AUTH_TOKEN_ERROR: Unexpected response format", ['response' => $response]);
                return [
                    'status' => 'error',
                    'message' => 'Unexpected response format'
                ];
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error("AUTH_TOKEN_ERROR: $errorMessage");
            
            return [
                'status' => 'error',
                'message' => $errorMessage
            ];
        }
    }
    /**
     * Initiates an STK push request.
     *
     * @param array $stkData Array containing STK push data.
     * @return array response or error message from the API.
     */

    public function lipaNaMpesaStk(array $stkData): array
    {
        
        try {
            
            $consumerKey = env('MPESA_CONSUMER_KEY');
            $consumerSecret = env('MPESA_CONSUMER_SECRET');
            $shortCode = env('MPESA_SHORTCODE');
            $passkey = env('MPESA_PASSKEY');
            $amount = $stkData['amount'];
            $partyA = $stkData['msisdn'];
            $accountReference = 'Finta Tech';
            $stkCallbackUrl = env('MPESA_STK_CALLBACK_URL');
            $partyB = env('MPESA_SHORTCODE');
            $transactionType = 'CustomerBuyGoodsOnline';
            $stkInitiateUrl = env('MPESA_STK_URL');


           $passwordValues = $this->getPassword($shortCode, $passkey);

            $password = $passwordValues['password'];
            $timestamp = $passwordValues['timestamp'];

            $accessToken = Cache::get('safaricom_stk_access_token');

            if (!$accessToken) {

                $response = $this->getAccessToken($consumerKey, $consumerSecret);

                if (isset($response['status']) && $response['status'] === 'error') {
                    return $response;
                }

                $accessToken = $response['access_token'];
                $expiry = $response['expires_in'];

                Cache::put('safaricom_stk_access_token', $accessToken, now()->addSeconds($expiry));
            }

            $postData = [
                'BusinessShortCode' => $shortCode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $transactionType,
                'Amount' => $amount,
                'PartyA' => $partyA,
                'PartyB' => $partyB,
                'PhoneNumber' => $partyA,
                'CallBackURL' => $stkCallbackUrl,
                'AccountReference' => $accountReference,
                'TransactionDesc' => $partyA . " has paid " . $amount . " to " . $shortCode
            ];

            $requestHeaders = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ];

            $ch = curl_init();

            curl_setopt_array($ch, array(
                CURLOPT_URL => $stkInitiateUrl,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $requestHeaders,
                CURLOPT_POSTFIELDS => json_encode($postData)
            ));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {

                $errorMessage = curl_error($ch);

                Log::error("STK_ERROR:" . $errorMessage);

                curl_close($ch);

                return [
                    'status' => 'error',
                    'message' => $errorMessage
                ];
            }

            $responseBody = json_decode($response, true);
            Log::info("STK: Request initiated", $responseBody);

            curl_close($ch);

            if (isset($responseBody['ResponseCode']) && $responseBody['ResponseCode'] === '0') {

                $this->saveStkPayment($responseBody, $shortCode);

                return [
                    'status' => 'success',
                    'message' => $responseBody

                ];
            }

            return [
                'status' => 'error',
                'message' => "STK error: " . json_encode($responseBody),
            ];
        } catch (\Exception $e) {

            $errorMessage = $e->getMessage();

            Log::error("STK_ERROR: $errorMessage");


            return [
                'status' => 'error',
                'message' => $errorMessage
            ];
        }
    }


    /**
     * Handles the M-PESA STK callback data and saves it to the database.
     *
     * @param array $data An associative array containing M-PESA STK callback data. 
     *                     Expected keys: 'merchant_request_id', 'checkout_request_id', 
     *                     'transaction_id', 'transaction_date', 'amount', 'msisdn'.
     * @return array An associative array indicating the result of the operation. 
     */
    public function handleStkCallbackData(array $callbackData): array
    {

        $merchantRequestId = $callbackData['MerchantRequestID'];
        $checkoutRequestId = $callbackData['CheckoutRequestID'];
        $resultCode = $callbackData['ResultCode'];
        $resultDesc = $callbackData['ResultDesc'];

        if ($resultCode !== 0) {

            Log::error("STK_CALLBACK_ERROR - Failed with code $resultCode - $resultDesc");

            return [
                'status' => 'error',
                'message' => $resultDesc
            ];
        }

        $callbackMetadata = $callbackData['CallbackMetadata']['Item'];

        $amount = null;
        $transactionDate = null;
        $msisdn = null;
        $transactionId = null;

        foreach ($callbackMetadata as $item) {
            switch ($item['Name']) {
                case 'Amount':
                    $amount = $item['Value'];
                    break;
                case 'MpesaReceiptNumber':
                    $transactionId = $item['Value'];
                    break;
                case 'TransactionDate':
                    $transactionDate = $item['Value'];
                    break;
                case 'PhoneNumber':
                    $msisdn = $this->sanitizeAndFormatMobile($item['Value']);
                    break;
                default:
                    break;
            }
        }


        $mpesaStkPayment = MpesaSTK::where('merchant_request_id', $merchantRequestId)
            ->where('checkout_request_id', $checkoutRequestId)
            ->first();

        if (!$mpesaStkPayment) {

            Log::error("STK_CALLBACK_ERROR: Record was not saved initially for $merchantRequestId  and CheckoutRequestID $checkoutRequestId");

            return [
                'status' => 'error',
                'message' => "Record not found for $merchantRequestId"
            ];
        }

        try {

            $mpesaStkPayment->transaction_id = $transactionId;
            $mpesaStkPayment->transaction_date = $transactionDate;
            $mpesaStkPayment->amount = $amount;
            $mpesaStkPayment->msisdn = $msisdn;
            $mpesaStkPayment->status = 'success';
            $mpesaStkPayment->result_code = $resultCode;
            $mpesaStkPayment->result_desc = $resultDesc;

            $mpesaStkPayment->save();

            return [
                'status' => 'success',
                'message' => "Entry for $transactionId updated successfully"
            ];
        } catch (\Exception $e) {

            Log::error("STK_CALLBACK_ERROR: Failed to update entry for {$transactionId} {$e->getMessage()}");

            return [
                'status' => 'error',
                'message' => "Failed to update Mpesa callback entry: {$e->getMessage()}"

            ];
        }
    }

    /**
     * Saves STK Payment details to the database.
     *
     * @param array $data Array containing 'MerchantRequestID' and 'CheckoutRequestID'.
     * @return void
     * @throws \Exception If saving fails.
     */
    private function saveStkPayment(array $data, string $shortcode): void
    {
        try {
            MpesaSTK::create([
                'merchant_request_id' => $data['MerchantRequestID'],
                'checkout_request_id' => $data['CheckoutRequestID'],
                'status' => 'pending',
                'result_code' => $data['ResponseCode'],
                'result_desc' => $data['ResponseDescription']
            ]);

            Log::info("STK: Payment Saved {$data['CheckoutRequestID']}");
        } catch (\Exception $e) {

            Log::error("STK_SAVE_ERROR: {$e->getMessage()}");

            throw new \Exception('Failed to save payment details: ' . $e->getMessage());
        }
    }



    /**
     * Generates the M-PESA password.
     *
     * @param int $shortCode The short code for the transaction.
     * @param string $passkey The passkey for the transaction.
     * @return array The encoded password and timestamp
     */

    private function getPassword(int $shortCode, string $passkey): array
    {

        $timestamp = Carbon::now()->format('YmdHis');

        $password  = base64_encode($shortCode . $passkey . $timestamp);

        return [

            'password' => $password,
            'timestamp' => $timestamp
        ];
    }

    /**
     * Fetches the access token from M-PESA.
     *
     * @param string $consumerKey The consumer key for the API.
     * @param string $consumerSecret The consumer secret for the API.
     * @return array The response containing the access token or an error message.
     */


     private function getAccessToken(string $consumerKey, string $consumerSecret): array
     {

         $mpesaAuthService = new MpesaAuthService;

         $url = env('MPESA_GENERATE_TOKEN_URL');

         $response = $mpesaAuthService->generateAccessToken($url, $consumerKey, $consumerSecret);
         Log::info($response);
         return $response;
     }
}