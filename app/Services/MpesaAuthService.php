<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MpesaAuthService

{
    public function generateAccessToken(string $url, string $consumerKey, string $consumerSecret): array
    {

        try {

            $client = new Client();

            $authToken = base64_encode("{$consumerKey}:{$consumerSecret}");

            $headers = [
                'Authorization' => 'Basic ' . $authToken
            ];


            $response = $client->get($url, [
                'headers' => $headers,
            ]);


            $responseBody = json_decode($response->getBody(), true);

            Log::info("AUTH_TOKEN: Token fetched");

            return [
                'access_token' => $responseBody['access_token'],
                'expires_in' => $responseBody['expires_in']
            ];
        } catch (\Exception $e) {

            $errorMessage = $e->getMessage();

            Log::error("AUTH_TOKEN_ERROR: $errorMessage");

            return [
                'status' => 'error',
                'message' => $errorMessage
            ];
        }
    }

}