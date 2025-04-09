<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\Setting\Entities\Setting;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendLoanApprovedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $loan;
    public $client;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($loan, $client)
    {
        $this->loan = $loan;
        $this->client = $client;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        $firstName = $this->client->first_name;
        $middleName = $this->client->middle_name;
        $lastName = $this->client->last_name;
        $phone = SmsService::formatMobileNumber($this->client->mobile);
        $loanAmount = number_format($this->loan->approved_amount);
        $message = "Dear $firstName $lastName, Your loan of ksh $loanAmount has been approved.Thank you";
        
        SmsService::sendSms($phone, $message);

        // Create message record
        Message::create([
            'client_id' => $this->client->id,
            'loan_id' => $this->loan->id,
            'message_description' => $message,
            'cost' => 0.00,
            'sent_by' => 'System Auto',
            'date_sent' => now()
        ]);
        
        Log::info('SMS logged to database');
         
            
       
    }

   
}