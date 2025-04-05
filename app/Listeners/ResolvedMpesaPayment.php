<?php

namespace App\Listeners;

use App\Models\Loan\Loan;
use App\Events\PaymentResolved;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResolvedMpesaPayment
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentResolved $event): void
    {
        $loandetails = Loan::with('transactions')
                     ->with('repayment_schedules')
                     ->with('client')
                     ->where('account_number', $BillRefNumber)
                     ->where('status', 'active')
                     ->first();
    }

    public function sendPaymentConfirmationSMS($firstname,$lastname,$phone_number, $amount_received, $outstanding_balance,$accountnumber,$message)
        {
            $message=$message;
            $url = env('SMS_URL');
            $sms_api_key = env('SMS_API_KEY');
            $sms_sender_id = env('SMS_SENDER_ID');

            // send SMS
            $response = Http::post($url, [
                'email' => 'josephm2800@gmail.com',
                'to' => $phone_number,
                'from' => $sms_sender_id,
                'auth' => $sms_api_key,
                'message' => $message,
            ]);

            return $response->ok();
        }
}
