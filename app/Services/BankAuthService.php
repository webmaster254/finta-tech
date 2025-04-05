<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class BankAuthService
{
    private $baseUrl = 'https://uat-app.astraafrica.co:2017/dev-portal/v2/auth/token';

    private $credentials = [
        'client_id' => '3wCg61eusw4Cj0cS',
        'client_secret' => '2PzcgWLcHHUD7pliMuIxpZOPGoDxNbKY',
        'username' => 'fanikisha61',
        'password' => 'WJG1dMWNiM3jJcMLyY',
        'grant_type' => 'password'
    ];

    /**
     * Get token using curl with detailed error handling
     *
     * @return string|null
     * @throws Exception
     */
    public function getTokenWithCurl()
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->baseUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30, // Set a reasonable timeout
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($this->credentials),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => false, // Only for development/testing
                CURLOPT_SSL_VERIFYHOST => false  // Only for development/testing
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            $info = curl_getinfo($curl);

            curl_close($curl);

            if ($error) {
                Log::error('Curl Error:', ['error' => $error, 'info' => $info]);
                throw new Exception("Curl error: $error");
            }

            $responseData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON Decode Error:', [
                    'response' => $response,
                    'error' => json_last_error_msg()
                ]);
                throw new Exception('Invalid JSON response');
            }

            if (!isset($responseData['access_token'])) {
                Log::error('No Access Token in Response:', ['response' => $responseData]);
                throw new Exception('No access token in response');
            }

            return $responseData['access_token'];

        } catch (Exception $e) {
            Log::error('Token Fetch Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Alternative implementation using Laravel's HTTP client
     *
     * @return string|null
     * @throws Exception
     */
    public function getTokenWithHttp()
    {
        try {
            $response = Http::withoutVerifying() // Only for development/testing
                ->asForm()
                ->withHeaders([
                    'Accept' => 'application/json'
                ])
                ->post($this->baseUrl, $this->credentials);

            if (!$response->successful()) {
                Log::error('HTTP Error:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception("HTTP error: {$response->status()} - {$response->body()}");
            }

            $responseData = $response->json();

            if (!isset($responseData['access_token'])) {
                Log::error('No Access Token in Response:', ['response' => $responseData]);
                throw new Exception('No access token in response');
            }

            return $responseData['access_token'];

        } catch (Exception $e) {
            Log::error('Token Fetch Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getTokenResponse()
    {
        try {

            $response = Http::withoutVerifying() // Only for development
                ->asForm()
                ->withHeaders([
                    'Accept' => 'application/json'
                ])
                ->post($this->baseUrl, $this->credentials);

            if (!$response->successful()) {
                Log::error('HTTP Error:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception("HTTP error: {$response->status()} - {$response->body()}");
            }

            $tokenData = $response->json();

            // Validate the response structure
            $requiredFields = [
                'access_token',
                'expires_in',
                'refresh_expires_in',
                'refresh_token',
                'token_type',
                'session_state',
                'scope'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($tokenData[$field])) {
                    Log::error('Missing Field in Token Response:', [
                        'missing_field' => $field,
                        'response' => $tokenData
                    ]);
                    throw new Exception("Missing required field: {$field}");
                }
            }

            // Cache the token data
            $this->cacheTokenData($tokenData);

            return $tokenData;

        } catch (Exception $e) {
            Log::error('Token Fetch Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getAccessToken()
    {
        $tokenData = $this->getTokenResponse();
        return $tokenData['access_token'];
    }
}
