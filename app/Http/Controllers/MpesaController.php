<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\MpesaC2B;
use App\Models\Loan\Loan;
use Illuminate\Http\Request;
use App\Events\LoanRepayment;
use App\Models\PaymentDetail;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Jobs\ProcessLoanPayment;
use App\Models\MpesaTransactions;
use App\Services\MpesaStkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Loan\LoanTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Traits\MobileFormattingTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Support\Renderable;

class MpesaController extends Controller
{

    protected MpesaStkService $mpesaStkService;

    use MobileFormattingTrait;

    public function __construct(MpesaStkService $mpesaStkService)
    {
       
        $this->mpesaStkService = $mpesaStkService;
    }


    // Generate an AccessToken using the Consumer Key and Consumer Secret
    public function generateAccessToken()
    {
        //$consumerKey='3B9ZaaDeimWgn1UNrVZpxOAtWkH2tEpr';
        $consumerKey = env('MPESA_CONSUMER_KEY');
       // $consumerSecret='WEOZYj7o5JLy0GfB';
        $consumerSecret = env('MPESA_CONSUMER_SECRET');
        $tokenUrl= env('MPESA_GENERATE_TOKEN_URL');

        $response=Http::withBasicAuth($consumerKey,$consumerSecret)->get($tokenUrl);
        return $response['access_token'];

    }

         public function registerUrl(){
            $accessToken=$this->generateAccessToken();
            $registerUrl= env('MPESA_REGISTER_URL');
            $ShortCode= env('MPESA_SHORTCODE');
            $ResponseType='Completed';  //Cancelled
            $ConfirmationURL= env('MPESA_CONFIRMATION_URL');
            $ValidationURL= env('MPESA_VALIDATION_URL');

            $response=Http::withToken($accessToken)->post($registerUrl,[
                'ShortCode'=>$ShortCode,
                'ResponseType'=>$ResponseType,
                'ConfirmationURL'=>$ConfirmationURL,
                'ValidationURL'=>$ValidationURL
            ]);

            return $response;
        }

      public function registerPullApiUrl()
        {
            $accessToken = $this->generateAccessToken();
            $registerUrl = 'https://api.safaricom.co.ke/pulltransactions/v1/register';

            // Initialize cURL
            $curl = curl_init();

            // Request payload
            $payload = json_encode([
                'ShortCode' => '322113',
                'ResponseType' => 'Pull',
                'NominatedNumber' => '254742333999',
                'CallBackURL' => 'https://portal.fanikishamicrofinancebank.com/confirmationpull'
            ]);

            // Set cURL options
            curl_setopt_array($curl, [
                CURLOPT_URL => $registerUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ],
            ]);

            // Execute cURL request
            $response = curl_exec($curl);
            $err = curl_error($curl);

            // Close cURL session
            curl_close($curl);

            if ($err) {
                return response()->json([
                    'ResponseRefID' => null,
                    'Response Status' => '1001',
                    'ShortCode' => '322113',
                    'Response Description' => 'Error: ' . $err
                ], 500);
            }

            // Decode the response
            $responseData = json_decode($response, true);

            // Format the response according to the required structure
            return response()->json([
                'ResponseRefID' => $responseData['ResponseRefID'] ?? uniqid('REF-'),
                'Response Status' => $responseData['Response Status'] ?? '1000',
                'ShortCode' => $responseData['ShortCode'] ?? '322113',
                'Response Description' => $responseData['Response Description'] ?? 'Short Code 322113 Registered Successfully'
            ]);
        }


        public function pullTransactions($start_date, $end_date){
              $accessToken = $this->generateAccessToken();
            $url = env('MPESA_PULL_TRANSACTION_URL');
            
            // Initialize cURL
            $curl = curl_init();
            
            // Request payload
            $payload = json_encode([

	            "ShortCode"=>env('MPESA_SHORTCODE'),
            	"StartDate"=>$start_date,
            	"EndDate"=>$end_date,
            	"OffSetValue"=>"0"

            ]);

            // Set cURL options
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ],
            ]);

            // Execute cURL request
            $response = curl_exec($curl);
            $err = curl_error($curl);
            
            // Close cURL session
            curl_close($curl);

            if ($err) {
                return response()->json([
                    'ResponseRefID' => uniqid('ERR-'),
                    'ResponseCode' => '1001',
                    'ResponseMessage' => 'Error: ' . $err,
                    'Response' => []
                ], 500);
            }

            // Decode the response
            $responseData = json_decode($response, true);
            
            // Format the response
            $formattedResponse = [
                'ResponseRefID' => $responseData['ResponseRefID'] ?? uniqid('REF-'),
                'ResponseCode' => $responseData['ResponseCode'] ?? '1000',
                'ResponseMessage' => $responseData['ResponseMessage'] ?? 'Success',
                'Response' => []
            ];

         // Process and save each transaction
         if (!empty($responseData['Response']) && isset($responseData['Response'][0])) {
                $transactions = $responseData['Response'][0];
                foreach ($transactions as $transaction) {
                    try {
                        // Check if transaction already exists
                        if (!MpesaC2B::transactionExists($transaction['transactionId']) && !LoanTransaction::transactionExists($transaction['transactionId'])) {
                            // Save the transaction
                           
                           
                            MpesaTransactions::createFromResponse($transaction, $formattedResponse);
                        } else {
                            Log::info('Skipping duplicate transaction:', ['transaction_id' => $transaction['transactionId']]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to save Mpesa transaction:', [
                            'error' => $e->getMessage(),
                            'transaction' => $transaction
                        ]);
                    }
                }
            }
            Log::info('Pull transactions response: ' . json_encode($responseData));

            return response()->json($responseData);

        }

        public function ConfirmationPull()
            {

                $data=file_get_contents('php://input');
                    // Check if data is valid JSON
                    $response = json_decode($data, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Log error and return response with error message
                        Log::error('Invalid JSON received: ' . json_last_error_msg());
                        return response()->json([
                            'ResultCode' => 1,
                            'ResultDesc' => 'Invalid JSON received'
                        ]);
                    }
                }
        //validation response
        public function validationResponse($result_code, $result_description)
        {
            $result = json_encode([
                'ResultCode' => $result_code,
                'ResultDesc' => $result_description,
            ]);
            $response = new Response();
            $response->headers->set('Content-Type', 'application/json; charset=utf-8');
            $response->setContent($result);

            return $response;
        }

        public function Validation(){
            Log::info('Validation endpoint has been hit');
            $data=file_get_contents('php://input');
            Storage::disk('local')->put('validation.txt',$data);
            $result_code = "0";
            $result_description = "Accepted validation request";

            //validation logic
            // return $this->validationResponse($result_code, $result_description);

            return response()->json([
                'ResultCode'=>0,
                'ResultDesc'=>'Accepted'
            ]);

            /*
            return response()->json([
                'ResultCode'=>'C2B00011',
                'ResultDesc'=>'Rejected'
            ])
            */
        }


         public function Confirmation()
         {

                $data=file_get_contents('php://input');
                    // Check if data is valid JSON
                    $response = json_decode($data, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Log error and return response with error message
                        Log::error('Invalid JSON received: ' . json_last_error_msg());
                        return response()->json([
                            'ResultCode' => 1,
                            'ResultDesc' => 'Invalid JSON received'
                        ]);
                    }


                    //save data to DB
                    $TransactionType=$response['TransactionType'] ?? null;
                    $TransID=$response['TransID'] ?? null;
                    $TransTime=$response['TransTime'] ?? null;
                    $TransAmount=$response['TransAmount'] ?? null;
                    $BusinessShortCode=$response['BusinessShortCode'] ?? null;
                    $BillRefNumber=$response['BillRefNumber'] ?? null;
                    $InvoiceNumber=$response['InvoiceNumber'] ?? null;
                    $OrgAccountBalance=$response['OrgAccountBalance'] ?? null;
                    $ThirdPartyTransID=$response['ThirdPartyTransID'] ?? null;
                    $MSISDN=$response['MSISDN'] ?? null;
                    $FirstName=$response['FirstName'] ?? null;
                    $MiddleName=$response['MiddleName'] ?? null;
                    $LastName=$response['LastName'] ?? null;

                    //save c2b data
                    $c2b=new MpesaC2B;
                    $c2b->Transaction_type =$TransactionType;
                    $c2b->Transaction_ID=$TransID;
                    $c2b->Transaction_Time=$TransTime;
                    $c2b->Amount=$TransAmount;
                    $c2b->Business_Shortcode=$BusinessShortCode;
                    $c2b->Account_Number=$BillRefNumber;
                    $c2b->status=$InvoiceNumber;
                    $c2b->Organization_Account_Balance=$OrgAccountBalance;
                    $c2b->ThirdParty_Transaction_ID=$ThirdPartyTransID;
                    $c2b->Phonenumber=$MSISDN;
                    $c2b->FirstName=$FirstName;
                    $c2b->MiddleName=$MiddleName;
                    $c2b->LastName=$LastName;
                    $c2b->save();

                   
                    ProcessLoanPayment::dispatch($response);
                    return response()->json([
                        'ResultCode'=>0,
                        'ResultDesc'=>'Accepted'
                    ]);


        }

         



    public function b2c(Request $request){
        $accessToken=$this->generateAccessToken();
        //return $accessToken;
        $InitiatorName='testapi';
        $InitiatorPassword='Safaricom123';
        $path=Storage::disk('local')->get('ProductionCertificate.cer');
        $pk=openssl_pkey_get_public($path);

        openssl_public_encrypt(
            $InitiatorPassword,
            $encrypted,
            $pk,
            $padding=OPENSSL_PKCS1_PADDING

        );

        //$encrypted
        $SecurityCredential=base64_encode($encrypted);
        $CommandID='BusinessPayment'; //BusinessPayment PromotionPayment
        $Amount=3000;
        $PartyA=600998;
        $PartyB=254708374149;
        $Remarks='remarks';
        $QueueTimeOutURL='https://account.fanikishagroup.co.ke/b2ctimeout';
        $ResultURL='https://account.fanikishagroup.co.ke/b2cresult';
        $Occassion='occassion';
        $url='https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';

        $response=Http::withToken($accessToken)->post($url,[
            'InitiatorName'=>$InitiatorName,
            'SecurityCredential'=>$SecurityCredential,
            'CommandID'=>$CommandID,
            'Amount'=>$Amount,
            'PartyA'=>$PartyA,
            'PartyB'=>$PartyB,
            'Remarks'=>$Remarks,
            'QueueTimeOutURL'=>$QueueTimeOutURL,
            'ResultURL'=>$ResultURL,
            'Occassion'=>$Occassion

        ]);

        return $response;



       }

       public function b2cResult(){
        $data=file_get_contents('php://input');
        // Storage::disk('local')->put('b2cresponse.txt',$data);

        // Check if data is valid JSON
                    $payload = json_decode($data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Log error and return response with error message
                        Log::error('Invalid JSON received: ' . json_last_error_msg());
                        return response()->json([
                            'ResultCode' => 1,
                            'ResultDesc' => 'Invalid JSON received'
                        ]);
                    }

        if ($payload->Result->ResultCode == 0) {
                $b2cDetails = [
                    'ResultType' => $payload->Result->ResultType,
                    'ResultCode' => $payload->Result->ResultCode,
                    'ResultDesc' => $payload->Result->ResultDesc,
                    'OriginatorConversationID' => $payload->Result->OriginatorConversationID,
                    'ConversationID' => $payload->Result->ConversationID,
                    'TransactionID' => $payload->Result->TransactionID,
                    'TransactionAmount' => $payload->Result->ResultParameters->ResultParameter[0]->Value,
                    'RegisteredCustomer' => $payload->Result->ResultParameters->ResultParameter[2]->Value,
                    'ReceiverPartyPublicName' => $payload->Result->ResultParameters->ResultParameter[4]->Value, //Details of Recepient
                    'TransactionDateTime' => $payload->Result->ResultParameters->ResultParameter[5]->Value,
                    'B2CChargesPaidAccountAvailableFunds' => $payload->Result->ResultParameters->ResultParameter[3]->Value, //Charges Paid Account Balance
                    'B2CUtilityAccountAvailableFunds' => $payload->Result->ResultParameters->ResultParameter[6]->Value, //Utility Account Balance
                    'B2CWorkingAccountAvailableFunds' => $payload->Result->ResultParameters->ResultParameter[7]->Value, //Working Account Balance
                ];
                $b2c=new MpesaB2C;
                $b2c->fill($b2cDetails)->save();

        }


       }

       public function b2cTimeout(){
        $data=file_get_contents('php://input');
        Storage::disk('local')->put('b2ctimeout.txt',$data);


       }


        /**
     * Initiates an M-PESA STK request.
     *
     * @param Request $request The incoming request containing STK data.
     * @return \Illuminate\Http\JsonResponse JSON response with API result or error message.
     */

    public function initiateStkRequest(Request $request): JsonResponse
    {

        $requestData = Validator::make($request->all(), [

            'amount' => 'required|numeric',
            'msisdn' => 'required|numeric',
         
        ]);

         if ($requestData->fails()) {
            return response()->json($requestData->errors()->first(), 422);
        }

        $stkData = $requestData->validated();

        $stkData['msisdn'] = $this->sanitizeAndFormatMobile($stkData['msisdn']);


        $response = $this->mpesaStkService->lipaNaMpesaStk($stkData);

        if ($response['status'] === 'error') {

            return response()->json($response, 500);
        }
     
        return response()->json($response, 200);
    }


    /**
     * Handle M-PESA STK callback data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function handleStkCallback(Request $request): JsonResponse
    {

         $data=file_get_contents('php://input');
        Storage::disk('local')->put('stkcallback.txt',$data);
        $callbackData = $request->input('Body.stkCallback', []);

        $response = $this->mpesaStkService->handleStkCallbackData($callbackData);

        if ($response['status'] === 'error') {

            return response()->json($response, 500);
        }

        return response()->json($response, 200);
    }
}
