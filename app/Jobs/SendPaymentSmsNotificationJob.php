<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPaymentSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $clientWithActiveLoan;
    public $loandata;
    public $total_due;
    public $amount;

    /**
     * Create a new job instance.
     */
    public function __construct($clientWithActiveLoan,$loandata,$total_due,$amount)
    {
        $this->clientWithActiveLoan = $clientWithActiveLoan;
        $this->loandata = $loandata;
        $this->total_due = $total_due;
        $this->amount = $amount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $firstName = $this->clientWithActiveLoan->first_name;
        $middleName = $this->clientWithActiveLoan->middle_name;
        $lastName = $this->clientWithActiveLoan->last_name;
        $phone = $this->clientWithActiveLoan->mobile;
        $accountNumber = $this->clientWithActiveLoan->account_number;
        $totalDue = $this->loandata->repayment_schedules->sum('total_due');
        $amount_received = $this->amount;


        Storage::disk('local')->put('sms.txt',$message);
        if($totalDue > 0){
            $message = "Dear $firstName $lastName, your payment of  KES " . number_format($amount_received) .
            " has been received. Your Outstanding loan balance is KES " . number_format($totalDue). " Thank you for your payment.";
            SmsService::sendSms($phone, $message);
        } else {
            $message = "Dear $firstName $lastName, your payment of  KES " . number_format($amount_received) .
                            " has been received. Congratulations! You have successfully repaid your full loan.Thank you . ";
            SmsService::sendSms($phone, $message);
           $this->loandata->update(['status' => 'closed']);
        }

    }

    public function sendPaymentConfirmationSMS($firstname,$middlename,$lastname,$phone_number, $amount_received, $outstanding_balance,$accountnumber,$message) {
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
