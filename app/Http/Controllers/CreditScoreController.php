<?php

namespace App\Http\Controllers;

use App\Services\CreditScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreditScoreController extends Controller
{
    protected $creditScoringService;
    
    /**
     * Create a new controller instance.
     *
     * @param CreditScoringService $creditScoringService
     * @return void
     */
    public function __construct(CreditScoringService $creditScoringService)
    {
        $this->creditScoringService = $creditScoringService;
    }
    
    /**
     * Calculate credit score for a loan application.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateScore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'has_full_kyc' => 'required|boolean',
            'current_loan_amount' => 'required|numeric|min:0',
            'daily_installment_percentage' => 'nullable|numeric|min:0|max:100',
            'cleared_on_time' => 'nullable|boolean',
            'business_age_years' => 'nullable|numeric|min:0',
            'defaults_past_2years' => 'nullable|integer|min:0',
            'net_profit' => 'nullable|numeric',
            'late_payments' => 'nullable|array',
            'late_payments.*.days_late' => 'required|integer|min:0',
            'late_payments.*.amount' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $scoreResult = $this->creditScoringService->calculateScore($request->all());
        
        return response()->json([
            'status' => 'success',
            'data' => $scoreResult
        ]);
    }
}