<?php

namespace App\Http\Controllers\v1;

use Log;
use Exception;
use App\Models\User;
use App\Models\Client;
use App\Models\Currency;
use App\Models\MpesaC2B;
use App\Models\Loan\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        // Check if loan_officer_id is provided
        if (!$request->has('loan_officer_id')) {
            return response()->json(['message' => 'Loan officer ID is required'], 400);
        }


        $user = $request->loan_officer_id;
        try{
        $totalClients = (int) Client::where('status', 'active')
                                    ->where('loan_officer_id', $user)->count();

                                     // Check if any arrears data is found

        $loans = Loan::where('loan_officer_id', $user)
                       ->with(['repayment_schedules' => function ($query) {
                           $query->select('loan_repayment_schedules.*');
                       }])
                        ->whereIn('status', ['active', 'closed'])
                       ->get(['id',
                       'approved_amount',
                       'principal',
                       'interest_disbursed_derived',
                       'principal_disbursed_derived',
                       'fees_repaid_derived',
                       'penalties_repaid_derived'])
                       ->keyBy('id');



                $totalLoans = $loans->count();
                $principalDisbursed = $loans->sum('principal_disbursed_derived');
                $interestDisbursed = $loans->sum('interest_disbursed_derived');
                $totalLoanDisbursed = $principalDisbursed + $interestDisbursed ;

                $loansRepayment = $loans->flatMap(function ($loan) {
                    return $loan->repayment_schedules;
                });

                $principalRepayment = $loansRepayment->sum('principal_repaid_derived');
                $interestRepayment = $loansRepayment->sum('interest_repaid_derived');
                $feeRepayment = $loansRepayment->sum('fees_repaid_derived');
                $penaltyRepayment = $loansRepayment->sum('penalties_repaid_derived');

                $totalLoanRepayment = $principalRepayment + $interestRepayment + $feeRepayment + $penaltyRepayment;



                $totalLoanOutstanding = $loansRepayment->sum('total_due');
                $totalOutstandingArrears = $loansRepayment->where('total_due', '>', 0)
                                                        ->where('due_date', '<', Carbon::now())
                                                        ->sum('total_due');
                $currency = Currency::where('is_default', 1)->first();

                $formatNumber = function (int $number): string {
                    if ($number < 100000) {
                        return (string) Number::format($number);
                    } elseif ($number < 1000000) {
                        return  Number::abbreviate($number, precision: 2);
                    } else {
                        return  Number::abbreviate($number, precision: 2) ;
                    }
                };

                $par = $totalLoanOutstanding == 0 ? 0 : Number::format(($totalOutstandingArrears/$totalLoanOutstanding) * 100, precision: 2);


                return response()->json(
                    [
                        'totalclients' => $totalClients,
                        'totalLoans' => $totalLoans,
                        'totalLoanDisbursed' => $totalLoanDisbursed,
                        'totalLoanRepayment' => $totalLoanRepayment,
                        'totalLoanOutstanding' => $totalLoanOutstanding,
                        'totalOutstandingArrears' => $totalOutstandingArrears,
                        'par' => $par
                    ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error fetching loans: ' . $e->getMessage());

            return response()->json(['message' => 'An error occurred while fetching data'], 500);
        }
    }

    public function monthlyPar(Request $request)
    {
        // Check if loan_officer_id is provided
        if (!$request->has('loan_officer_id')) {
            return response()->json(['message' => 'Loan officer ID is required'], 400);
        }

        $user = $request->loan_officer_id;
        try{
        $loansRepayment = Loan::query()
            ->join('loan_repayment_schedules', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
            ->where('loan_officer_id', $user)
            ->whereMonth('due_date', Carbon::now()->month)
            ->get(['loans.id', 'total_due', 'due_date']);



        $totalLoanOutstanding = $loansRepayment->sum('total_due');

        $totaloutstandingArrears = $loansRepayment->where('total_due', '>', 0)
                                ->where('due_date', '<', Carbon::now())
                                ->sum('total_due');


            if ($totalLoanOutstanding > 0) {
                $par = Number::format(($totaloutstandingArrears / $totalLoanOutstanding) * 100, 2);
            } else {
                // Handle division by zero or other potential issues
                $par = 0;
            }

            return response()->json(
                [
                    'monthlypar' => $par
                ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error fetching loans with repayments: ' . $e->getMessage());

            return response()->json(['message' => 'An error occurred while fetching data'], 500);
        }
    }




    public function clientsWithArrears(Request $request)
    {
         // Check if loan_officer_id is provided
         if (!$request->has('loan_officer_id')) {
            return response()->json(['message' => 'Loan officer ID is required'], 400);
        }
         $search = $request->query('search');
        $user = $request->loan_officer_id;
        $perPage = $request->query('per_page', 15); // Get items per page from query or default to 15


        try {
        $arrearsData = Loan::query() ->where('loans.loan_officer_id', '=', $user)->join('loan_repayment_schedules', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
            ->whereDate('loan_repayment_schedules.due_date', '<' ,Carbon::today())

            ->where('loan_repayment_schedules.total_due', '>', 0)
            ->join('clients', 'loans.client_id', '=', 'clients.id')
            ->where('loans.status', 'active')
            ->selectRaw("CONCAT(clients.first_name, ' ', clients.last_name) AS client, clients.mobile,
             clients.account_number,
               loans.client_id, loans.expected_maturity_date,
               loans.disbursed_on_date, loans.id,loans.status,
               (SELECT submitted_on FROM loan_transactions WHERE loan_id = loans.id
               ORDER BY submitted_on DESC LIMIT 1) AS last_payment_date,
               loans.principal,loans.loan_term,SUM(loan_repayment_schedules.total_due ) AS arrears,
               (SELECT DATEDIFF(CURDATE(), MIN(due_date))
                    FROM loan_repayment_schedules
                    WHERE loan_id = loans.id
                    AND due_date < CURDATE()
                    AND total_due > 0
                    AND paid_by_date IS NULL) AS days_in_arrears")
               ->groupBy('loans.id')
               ->orderBy('loans.disbursed_on_date', 'desc'); // Sort by loans.disbursed_on_date in descending order
               if ($search) {
                $arrearsData->whereHas('client', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('middle_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('mobile', 'LIKE', "%{$search}%")
                            ->orWhere('account_number', 'LIKE', "%{$search}%");
                    });
                });
            }
           $arrearsData = $arrearsData->paginate($perPage);


             return response()->json(['arrears' => $arrearsData]);
        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching clients with arrears: ' . $e->getMessage());

            return response()->json(['message' => 'An error occurred while fetching data'], 500);
        }
    }

    public function mpesaPayments(Request $request)
    {
        $perPage = $request->query('per_page', 15); // Get items per page from query or default to 15

        $payments = MpesaC2B::where('created_at', '>=', now()->subMonths(3)) // Filter for the past 3 months
            ->orderBy('created_at', 'desc') // Sort by created_at in descending order
            ->paginate($perPage); // Use paginate for pagination

        return response()->json(
            [
                'payments' => $payments // Return the paginated Mpesa payments
            ]);
    }


    public function totalDisbursementPerMonth(Request $request)
    {
        $loanOfficerId = $request->query('loan_officer_id');
        $currentYear = 2025;

        $totalDisbursement = Loan::where('loan_officer_id', $loanOfficerId)
            ->whereYear('disbursed_on_date', $currentYear)
            ->selectRaw('SUM(approved_amount) as total_disbursement, MONTH(disbursed_on_date) as month')
            ->groupBy('month')
            ->get();

        return response()->json(['total_disbursement' => $totalDisbursement]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
