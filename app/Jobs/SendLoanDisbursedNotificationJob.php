<?php
namespace App\Jobs;

use App\Models\Message;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\Setting\Entities\Setting;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendLoanDisbursedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $loan;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($loan)
    {
        $this->loan = $loan;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $loan = $this->loan;
        $firstname=$loan->client->first_name;
        $middlename=$loan->client->middle_name;
        $lastname=$loan->client->last_name;
        $phone_number=$loan->client->mobile;
        $approved_amount=number_format($loan->approved_amount);
        $outstanding_balance=number_format($loan->repayment_schedules->sum('total_due'));
        $firstSchedule = $loan->repayment_schedules->first();
        $due_date = $firstSchedule ? Carbon::parse($firstSchedule->due_date)->format('d/m/Y') : 'N/A';

        $message = "Hi $firstname $middlename $lastname, Your loan of ksh $approved_amount has been disbursed. Your outstanding balance is ksh $outstanding_balance. and Your Due date is $due_date. Thank you";
        SmsService::sendSms($phone_number, $message);
        Log::info('disbursed notification sent .',[ 'message' => $message]);

        // Create message record
        Message::create([
            'client_id' => $this->loan->client->id,
            'loan_id' => $this->loan->id,
            'message_description' => $message,
            'cost' => 0.00,
            'sent_by' => 'System Auto',
            'date_sent' => now()
        ]);
        
        Log::info('SMS logged to database');
    }

       
}