<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentNotificationMail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DtbController extends BaseController
{


    public function generateToken(): string
    {
        $clientId = '3wCg61eusw4Cj0cS';
        $clientSecret = '2PzcgWLcHHUD7pliMuIxpZOPGoDxNbKY';
        $username = 'fanikisha61';
        $password = 'WJG1dMWNiM3jJcMLyY';

        $client = new Client();
        $response = $client->post('https://uat-app.astraafrica.co:2017/dev-portal/v2/auth/token', [
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $username,
                'grant_type' => 'password',
                'password' => $password,
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true)['access_token'];
    }

public function DtbB2C()
{
    $token = $this->generateToken(); // Get the token using the generateToken method

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://uat-app.astraafrica.co:2017/fiorano/mno/mpesa/b2c',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
            "identifier": {
                "referenceID": "FI1628838604",
                "channel": "API"
            },
            "payload": {
                "beneficiaryMsisdn": "254708374149",
                "transactionAmount": 50,
                "customerMsisdn": "254723222222",
                "customerName": "Joe Win",
                "accountNumber": "0266795001",
                "branchCode": "041",
                "currency": "KES",
                "mnoCode": "MPESA",
                "narration": "TEST_AFTER_LATEST_CHANGES",
                "transactionType": "TransferFromBankToCustomer",
                "resultUrl": "https://portal.fanikishamicrofinancebank.com/dtb/confirmation"
            }
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token // Pass the token in the Authorization header
        ),
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    // Decode the API response
    $responseData = json_decode($response, true);

    // Format the response with actual values
    return response()->json([
        'Status' => 'SUCCESS',
        'responseCode' =>  '200',
        'responseDescription' => 'Acknowledged',
        'externalRef' => 'TESTChannelRef' . time()
    ]);
}

public function bankBalance()
{
    $token = $this->generateToken();
    $curl = curl_init();

    $postData = json_encode(['accno' => '0266795001']);

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://uat-app.astraafrica.co:2017/fiorano/statement/balance',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}

public function validateID()
{
    $accessToken = $this->generateToken(); // Get the token using the generateToken method

    $curl = curl_init();

    $postData = json_encode([
        'IPRS' => [
            'Id_Number' => '33206786',
            'Serial_Number' => '23344654767',
            'Passport_Number' => '123456789'
        ]
    ]);

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://uat-app.astraafrica.co:2017/fiorano/customeraccounts/iprs',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}


public function Confirmation()
         {
             Log::info('confirmation endpoint has been hit');
            $data = file_get_contents('php://input');
    
            // Check if data is valid JSON
            $response = json_decode($data, true);
    
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Log error and return response with error message
                Log::error('Invalid JSON received: ' . json_last_error_msg());
                return response()->json([
                    'xref' => '',
                    'user_reference' => '',
                    'ack_code' => '01',
                    'ack_description' => 'Failed to parse JSON'
                ]);
            }
    
            try {
                // Convert the response array back to a JSON string before saving
                $jsonString = json_encode($response, JSON_PRETTY_PRINT);
                if ($jsonString === false) {
                    throw new \Exception('Failed to encode response to JSON');
                }
                
                Storage::disk('local')->put('b2cresponse.txt', $jsonString);
                
                Log::info('Successfully saved response to b2cresponse.txt');
                
                return response()->json([
                    'xref' => $response['xref'],
                    'user_reference' => 'FAN',
                    'ack_code' => '00',
                    'ack_description' => 'Success'
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to save response: ' . $e->getMessage());
                return response()->json([
                    'ResultCode' => 1,
                    'ResultDesc' => 'Internal server error'
                ], 500);
            }

     }


     public function sendPaymentNotification()
     {
         // Parse the incoming JSON request body
        $transaction = file_get_contents('php://input');

        $response = json_decode($transaction, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Log error and return response with error message

            return response()->json([
                'xref' => '',
                'user_reference' => '',
                'ack_code' => '01',
                'ack_description' => 'Failed to parse JSON'
            ], 500);
        }

        try {
            
           Log:info('Dtb IPN Payload:',[
                'response'=> $response
                ]);
            Storage::disk('local')->put('ipnnotification.txt', json_encode($response, JSON_PRETTY_PRINT));
            // Send email
            Mail::to('fanikishamicrofinancebank@gmail.com')
                ->cc('josephm2800@gmail.com')
                ->send(new PaymentNotificationMail($response));
            

            return response()->json([
                'xref' => $response['xref'],
                'user_reference' => 'FAN',
                'ack_code' => '00',
                'ack_description' => 'Success'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment notification email: ' . $e->getMessage());

            return response()->json([
                'xref' => $response['xref'],
                'user_reference' => 'FAN',
                'ack_code' => '01',
                'ack_description' => 'Failed to send email'
            ], 500);
        }
     }



}
