<?php

namespace App\Services;

class CreditScoringService
{
    /**
     * Calculate the credit score for a loan applicant
     *
     * @param array $data The applicant data
     * @return array Credit score and decision information
     */
    public function calculateScore(array $data): array
    {
        $score = $this->calculateBaseScore($data);
        $score += $this->calculatePaymentHistoryScore($data);
        $score += $this->calculateBusinessAgeScore($data);
        $score += $this->calculatePreviousDefaultsScore($data);
        $score += $this->calculateBusinessStatisticsScore($data);
        
        return [
            'score' => $score,
            'decision' => $this->makeDecision($score, $data['current_loan_amount'] ?? 0),
        ];
    }
    
    /**
     * Calculate the base score
     *
     * @param array $data
     * @return int
     */
    private function calculateBaseScore(array $data): int
    {
        // Base score of 500 if full KYC is provided
        return isset($data['has_full_kyc']) && $data['has_full_kyc'] ? 500 : 0;
    }
    
    /**
     * Calculate score based on payment history
     *
     * @param array $data
     * @return float
     */
    private function calculatePaymentHistoryScore(array $data): float
    {
        $score = 0;
        
        // Daily installment paid on time
        if (isset($data['daily_installment_percentage'])) {
            $percentage = $data['daily_installment_percentage'];
            
            if ($percentage == 100) {
                $score += 5;
            } elseif ($percentage >= 90) {
                $score += 4;
            } elseif ($percentage >= 80) {
                $score += 3;
            } elseif ($percentage >= 70) {
                $score += 2;
            } elseif ($percentage >= 60) {
                $score += 1;
            } elseif ($percentage >= 41) {
                $score += 0.5;
            }
            // Below 40% adds 0 points
        }
        
        // Cleared on or before due date
        if (isset($data['cleared_on_time']) && $data['cleared_on_time']) {
            $score += 5;
        }
        
        // Late payments penalties
        if (isset($data['late_payments'])) {
            foreach ($data['late_payments'] as $payment) {
                $days_late = $payment['days_late'] ?? 0;
                $amount = $payment['amount'] ?? 0;
                
                $score += $this->calculateLatePenalty($days_late, $amount);
            }
        }
        
        return $score;
    }
    
    /**
     * Calculate penalty for late payments
     *
     * @param int $days_late
     * @param float $amount
     * @return float
     */
    private function calculateLatePenalty(int $days_late, float $amount): float
    {
        $penalty = 0;
        
        // Less than 7 days late
        if ($days_late < 7 && $days_late > 0) {
            if ($amount <= 20000) {
                $penalty = -1 * ceil($amount / 1000);
            } elseif ($amount <= 50000) {
                $penalty = -30;
            } else {
                $penalty = -50;
            }
        }
        // 7-30 days late
        elseif ($days_late >= 7 && $days_late <= 30) {
            if ($amount <= 20000) {
                $penalty = -3 * ceil($amount / 1000);
            } elseif ($amount <= 50000) {
                $penalty = -75;
            } else {
                $penalty = -100;
            }
        }
        // 30-60 days late
        elseif ($days_late > 30 && $days_late <= 60) {
            if ($amount <= 20000) {
                $penalty = -10 * ceil($amount / 1000);
            } elseif ($amount <= 50000) {
                $penalty = -150;
            } else {
                $penalty = -200;
            }
        }
        // 60-90 days late
        elseif ($days_late > 60 && $days_late <= 90) {
            if ($amount <= 20000) {
                $penalty = -15 * ceil($amount / 1000);
            } elseif ($amount <= 50000) {
                $penalty = -250;
            } else {
                $penalty = -300;
            }
        }
        // 90+ days late
        elseif ($days_late > 90) {
            if ($amount <= 20000) {
                $penalty = -20 * ceil($amount / 1000);
            } elseif ($amount <= 50000) {
                $penalty = -300;
            } else {
                $penalty = -400;
            }
        }
        
        return $penalty;
    }
    
    /**
     * Calculate score based on business age
     *
     * @param array $data
     * @return int
     */
    private function calculateBusinessAgeScore(array $data): int
    {
        if (!isset($data['business_age_years'])) {
            return 0;
        }
        
        $age = $data['business_age_years'];
        
        if ($age >= 6) {
            return 15;
        } elseif ($age >= 3) {
            return 10;
        } elseif ($age >= 1) {
            return 5;
        } else {
            return 1;
        }
    }
    
    /**
     * Calculate score based on previous defaults
     *
     * @param array $data
     * @return int
     */
    private function calculatePreviousDefaultsScore(array $data): int
    {
        if (!isset($data['defaults_past_2years'])) {
            return 0;
        }
        
        $defaults = $data['defaults_past_2years'];
        
        if ($defaults >= 5) {
            return -300;
        } elseif ($defaults >= 3) {
            return -250;
        } elseif ($defaults >= 2) {
            return -200;
        } elseif ($defaults == 1) {
            return -100;
        }
        
        return 0;
    }
    
    /**
     * Calculate score based on business statistics
     *
     * @param array $data
     * @return int
     */
    private function calculateBusinessStatisticsScore(array $data): int
    {
        if (!isset($data['net_profit']) || !isset($data['current_loan_amount']) || $data['current_loan_amount'] == 0) {
            return 0;
        }
        
        $profitRatio = $data['net_profit'] / $data['current_loan_amount'];
        
        if ($profitRatio >= 3) {
            return 100;
        } elseif ($profitRatio >= 2.5) {
            return 75;
        } elseif ($profitRatio >= 2) {
            return 50;
        } elseif ($profitRatio >= 1.5) {
            return 30;
        } elseif ($profitRatio >= 1) {
            return 25;
        }
        
        return 0;
    }
    
    /**
     * Make a loan decision based on credit score
     *
     * @param int $score
     * @param float $currentLoanAmount
     * @return array
     */
    private function makeDecision(int $score, float $currentLoanAmount): array
    {
        if ($score >= 800) {
            return [
                'risk_category' => 'Very Low Risk',
                'loan_limit' => $currentLoanAmount * 1.5,
                'classification' => 'Excellent',
                'decision' => 'Approve',
            ];
        } elseif ($score >= 700) {
            return [
                'risk_category' => 'Low Risk',
                'loan_limit' => $currentLoanAmount * 1.25,
                'classification' => 'Very Good',
                'decision' => 'Approve',
            ];
        } elseif ($score >= 600) {
            return [
                'risk_category' => 'Average Risk',
                'loan_limit' => $currentLoanAmount,
                'classification' => 'Good',
                'decision' => 'Approve',
            ];
        } elseif ($score >= 500) {
            return [
                'risk_category' => 'High Risk',
                'loan_limit' => $currentLoanAmount * 0.75,
                'classification' => 'Fair',
                'decision' => 'Review',
            ];
        } else {
            return [
                'risk_category' => 'Very High Risk',
                'loan_limit' => min($currentLoanAmount * 0.5, $currentLoanAmount),
                'classification' => 'Poor',
                'decision' => 'Reject',
            ];
        }
    }
}