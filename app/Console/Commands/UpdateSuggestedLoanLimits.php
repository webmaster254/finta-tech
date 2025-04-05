<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;

class UpdateSuggestedLoanLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:update-limits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update suggested loan limits for all clients';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clients = Client::all();

        foreach ($clients as $client) {
            $suggestedLimit = $this->calculateSuggestedLoanLimit($client);
            $client->update(['suggested_loan_limit' => $suggestedLimit]);
            $client->save();
            $score = $this->calculatePaymentHabitScore($client);
           $latestLoan = $client->loans()->where('status','closed')->latest()->first();
            $approvedAmount = $latestLoan ? $latestLoan->approved_amount : 10000;
            $this->info("Updated limit for client {$client->id}: {$suggestedLimit}");
            //$this->info("latest approved loan {$approvedAmount}");
            $this->info("score{$score}");
        }

        $this->info('All suggested loan limits updated successfully.');
    }

    private function calculateSuggestedLoanLimit($client)
    {
        // Implement your loan limit calculation logic here
        // This is a simplified example
        $latestLoan = $client->loans()->whereIn('status', ['closed', 'active'])->latest()->first();
        $baseLimit = $latestLoan ? $latestLoan->approved_amount : 10000;

        $score = $this->calculatePaymentHabitScore($client);
        $completedLoans = $client->loans()->where('status', 'closed')->count();

         if(!$completedLoans)
         {
            if($score<50)
            {
                $increaseAmount = 9000 * ($score / 100) ;
                $suggestedLimit = $baseLimit + $increaseAmount;
            }else if($score<70)
            {
              $increaseAmount =10000 * ($score / 100) ;
                $suggestedLimit = $baseLimit + $increaseAmount;
            }else
            {
                $increaseAmount = 8000 * ($score / 100) ;
                $suggestedLimit = $baseLimit + $increaseAmount;

            }
         }else {
            //$suggestedLimit = $baseLimit * (1 + ($score / 100)) * (1 + ($completedLoans / 10));
           
            if($score<50)
            {
                 $increaseAmount =8000 * (($score / 100) + ($completedLoans / 10)) ;
                $suggestedLimit = $baseLimit + $increaseAmount;
            }
            else if($score<70)
            {
              $increaseAmount =9000 * (($score / 100) + ($completedLoans / 10)) ;
                $suggestedLimit = $baseLimit + $increaseAmount;
            }
            else 
            {
                //$increaseAmount = 8000 * (($score / 100) + ($completedLoans / 10)) ;
                $suggestedLimit = $baseLimit + 8000;

            }

        }


        return round($suggestedLimit, -2); // Round to nearest 100
    }

    private function calculatePaymentHabitScore($client)
    {
        // Implement your payment habit score calculation logic here
        // This is a simplified example
        $score =0;
       $latestLoan = $client->loans()->whereIn('status', ['closed', 'active'])->latest()->first();
        if (!$latestLoan) return 0; // Default score for new clients

        $repayments = $latestLoan->repayment_schedules;
         $totalPayments = $repayments->filter(function ($repayment) {
            return !is_null($repayment->paid_by_date);
                     })->count();
        $onTimePayments = $repayments->filter(function ($repayment) {
            return $repayment->paid_by_date !== null && 
                   $repayment->paid_by_date <= $repayment->due_date;
        })->count();
         if(!$totalPayments)
         {
            $score = 0;
         }else {
            $score = ($onTimePayments / $totalPayments) * 100;
         }
        return $score;
    }
}
