<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\MpesaController;
use Carbon\Carbon;

class PullMpesaTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Get yesterday's date
            $yesterday = Carbon::yesterday();
            
            // Format dates in the required format (assuming YYYY-MM-DD format)
            $startDate = $yesterday->format('Y-m-d');
            $endDate = $yesterday->format('Y-m-d');

            // Create MpesaController instance
            $mpesaController = new MpesaController();

            // Pull transactions
            $result = $mpesaController->pullTransactions($startDate, $endDate);

            // Log the result
            \Log::info('Daily Mpesa transactions pull completed', [
                'date' => $startDate,
                'status' => $result->getStatusCode(),
                'response' => json_decode($result->getContent(), true)
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to pull Mpesa transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-throw the exception to mark the job as failed
        }
    }
}
