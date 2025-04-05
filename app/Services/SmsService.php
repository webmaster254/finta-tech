<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsService
{
    /**
     * Format mobile number to international format
     *
     * @param string $mobile
     * @return string
     */
    public static function formatMobileNumber($mobile)
    {
        $mobile = preg_replace('/[^0-9]/', '', $mobile);

        if (substr($mobile, 0, 1) === '0') {
            $mobile = '254' . substr($mobile, 1);
        }

        return $mobile;
    }

    /**
     * Send SMS using the configured SMS gateway
     *
     * @param string $phone_number
     * @param string $message
     * @return mixed
     */
    public static function sendSms($phone_number, $message)
    {
        $url = env('SMS_URL');
        $userid = env('SMS_USER_ID');
        $password = env('SMS_PASSWORD');
        $senderid = env('SMS_SENDER_ID');
        $api_key = env('SMS_API_KEY');
        
        // Prepare the POST data
        $postData = http_build_query([
            'userid' => $userid,
            'password' => $password,
            'mobile' => $phone_number,
            'msg' => $message,
            'senderid' => $senderid,
            'msgType' => 'text',
            'duplicatecheck' => 'true',
            'output' => 'json',
            'sendMethod' => 'quick'
        ]);
        
        // Initialize cURL session
        $curl = curl_init();
        
        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                "apikey: " . $api_key,
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded"
            ],
        ]);
        
        // Execute cURL session
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        // Check for errors
        if ($err) {
            Log::error('SMS sending failed: ' . $err);
        }
        
        // Close cURL session
        curl_close($curl);
        
        // Log the response
        Log::info('SMS Response:', ['response' => $response]);
        
        // Try to decode JSON response
        $decodedResponse = json_decode($response, true);
        
        // If response is valid JSON and contains status information
        if (is_array($decodedResponse) && isset($decodedResponse['status'])) {
            return $decodedResponse;
        }
        
        // If there was an error with cURL
        if ($err) {
            return [
                'status' => 'error',
                'message' => 'cURL Error: ' . $err
            ];
        }
        
        // If response is not valid JSON or doesn't contain expected structure
        // Try to determine success based on response content
        if (stripos($response, 'success') !== false) {
            return [
                'status' => 'success',
                'message' => 'SMS sent successfully',
                'raw_response' => $response
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Unknown response format: ' . substr($response, 0, 100),
                'raw_response' => $response
            ];
        }
    }
}
