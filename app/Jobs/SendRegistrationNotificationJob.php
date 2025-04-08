<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendRegistrationNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $client;

    /**
     * Create a new job instance.
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $phone = $this->client->mobile;
        $firstName = $this->client->first_name;
        $lastName = $this->client->last_name;
        $accountNumber = $this->client->account_number;
        $message = "Dear $firstName $lastName, Welcome to Finta. Your registration is completed successfully. Your registration Number is {$accountNumber}";

        Log::info($message);
        SmsService::sendSms($phone, $message);
        
        // Create message record
        Message::create([
            'client_id' => $this->client->id,
            'loan_id' => null,
            'message_description' => $message,
            'cost' => 0.00,
            'sent_by' => 'System Auto',
            'date_sent' => now()
        ]);
        
        Log::info('SMS logged to database');
    }
}