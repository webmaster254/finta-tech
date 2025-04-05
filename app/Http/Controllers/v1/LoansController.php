<?php

namespace App\Http\Controllers\v1;

use Log;
use Exception;
use App\Models\Loan\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;

class LoansController extends Controller
{

    public function index(Request $request)
    {

        // Check if loan_officer_id is provided
        if (!$request->has('loan_officer_id')) {
            return response()->json(['message' => 'Loan officer ID is required'], 400);
        }
        $search = $request->query('search');
        $user = $request->loan_officer_id;
        $perPage = $request->query('per_page', 15); // Get items per page from query or default to 15

        try{
            $loanData = Loan::where("loans.loan_officer_id", $user )->leftJoin("clients", "clients.id", "loans.client_id")
            ->leftJoin("loan_repayment_schedules", "loan_repayment_schedules.loan_id", "loans.id")
            ->leftJoin("loan_products", "loan_products.id", "loans.loan_product_id")
            ->leftJoin("branches", "branches.id", "loans.branch_id")
            ->leftJoin("users", "users.id", "loans.loan_officer_id")
            ->where("loans.loan_officer_id", $user )
            ->selectRaw("concat(clients.first_name,' ',clients.last_name) client,clients.mobile,clients.account_number,concat(users.first_name,' ',users.last_name) loan_officer,
            loans.id,loans.client_id,loans.approved_amount,loans.principal,loans.disbursed_on_date,loans.expected_maturity_date,
            loan_products.name loan_product,loans.status,loans.interest_disbursed_derived,branches.name branch, SUM(loan_repayment_schedules.principal) total_principal,
            SUM(loan_repayment_schedules.total_due) balance,
            (SELECT DATEDIFF(CURDATE(), MIN(due_date))
                    FROM loan_repayment_schedules
                    WHERE loan_id = loans.id
                    AND due_date < CURDATE()
                    AND total_due > 0
                    AND paid_by_date IS NULL) AS days_in_arrears,
            (SELECT SUM(loan_repayment_schedules.total_due)
                    FROM loan_repayment_schedules
                    WHERE loan_id = loans.id
                    AND due_date < CURDATE()
                    AND total_due > 0) AS arrears ")
            ->groupBy("loans.id")
            ->orderBy("loans.disbursed_on_date","desc");

            if ($search) {
                $loanData->whereHas('client', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('middle_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('mobile', 'LIKE', "%{$search}%")
                            ->orWhere('account_number', 'LIKE', "%{$search}%");
                    });
                });
            }
           $loanData = $loanData->paginate($perPage);

                // Check if any repayment schedule data is found
                if ($loanData->isEmpty()) {
                    return response()->json(['message' => 'No loans  found'], 404);
                }

                return response()->json(
                    [
                        'loans' => $loanData
                    ]);

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching loans : ' . $e->getMessage());

            return response()->json(['message' => 'An error occurred while fetching data'], 500);
        }
    }


    public function searchLoans(Request $request)
    {

         // Check if loan_officer_id is provided
         if (!$request->has('loan_officer_id')) {
            return response()->json(['message' => 'Loan officer ID is required'], 400);
        }
        $user = $request->loan_officer_id;
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15); // Get items per page from query or default to 15

        try {
            $loanData = Loan::join("clients", "loans.client_id", "=", "clients.id")
                ->join("users", "users.id", "=", "loans.loan_officer_id")
                ->leftJoin("loan_repayment_schedules", "loan_repayment_schedules.loan_id", "=", "loans.id")
                ->where(function ($query) use ($search) {
                    $query->whereHas('client', function ($query) use ($search) {
                        $query->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('middle_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('account_number', 'LIKE', "%{$search}%")
                            ->orWhere('mobile', 'LIKE', "%{$search}%");
                    });
                })
                ->where("loans.loan_officer_id", $user)
                ->selectRaw("concat(clients.first_name,' ',clients.last_name) client,clients.mobile,clients.account_number,concat(users.first_name,' ',users.last_name) loan_officer,
                loans.id,loans.client_id,loans.approved_amount,loans.principal,loans.disbursed_on_date,loans.expected_maturity_date,
                loans.status,loans.interest_disbursed_derived, SUM(loan_repayment_schedules.total_due) balance,
                SUM(loan_repayment_schedules.principal) total_principal,
                (SELECT DATEDIFF(CURDATE(), MIN(due_date))
                    FROM loan_repayment_schedules
                    WHERE loan_id = loans.id
                    AND due_date < CURDATE()
                    AND total_due > 0
                    AND paid_by_date IS NULL) AS days_in_arrears,
                (SELECT SUM(loan_repayment_schedules.total_due)
                    FROM loan_repayment_schedules
                    WHERE loan_id = loans.id
                    AND due_date < CURDATE()
                    AND total_due > 0) AS arrears")
                ->groupBy("loans.id")
                ->paginate($perPage);

            if ($loanData->isEmpty()) {
                return response()->json(['message' => 'No loans found'], 404);
            }

            return response()->json(
                [
                    'loans' => $loanData
                ]);

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Error searching loans : ' . $e->getMessage());

            return response()->json(['message' => 'An error occurred while searching loans'], 500);
        }
    }

    public function dailyRepaymentSchedule(Request $request)
    {
         // Check if loan_officer_id is provided
         if (!$request->has('loan_officer_id')) {
            return response()->json(['message' => 'Loan officer ID is required'], 400);
        }

        $user = $request->loan_officer_id;
        $perPage = $request->query('per_page', 15); // Get items per page from query or default to 15


        try {
            $scheduleData = Loan::query()
            ->where('loans.loan_officer_id', $user) // Specify the table name or alias for loan_officer_id
            ->join('loan_repayment_schedules', 'loan_repayment_schedules.loan_id', '=', 'loans.id')
            ->where('loan_repayment_schedules.due_date', now()->format('Y-m-d'))
            ->where('loan_repayment_schedules.total_due', '>', 0)
            ->join('clients', 'loans.client_id', '=', 'clients.id')
            ->where('loans.status', 'active')
            ->selectRaw("CONCAT(clients.first_name, ' ', clients.last_name) AS client, clients.mobile,
                clients.account_number,
                loans.client_id, loans.expected_maturity_date,
                loans.disbursed_on_date, loans.id AS loan_id,
                (SELECT submitted_on FROM loan_transactions WHERE loan_id = loans.id
                ORDER BY submitted_on DESC LIMIT 1) AS last_payment_date,
                loans.principal,loans.loan_term,loan_repayment_schedules.total_due")
                ->paginate($perPage); // Use paginate for pagination


            return response()->json(
                [
                    'schedules' => $scheduleData
                ]);
            } catch (\Exception $e) {
                // Log the error for debugging
                \Log::error('Error fetching clients with repayment schedules: ' . $e->getMessage());

                return response()->json(['message' => 'An error occurred while loan fetching data'], 500);
            }
    }

 public function show(Request $request, $id)
    {
        try {
            //$loan = Loan::find($id);
           $loanData = Loan::where("loans.id", $id)
            ->leftJoin("clients", "clients.id", "loans.client_id")
            ->leftJoin("loan_repayment_schedules", "loan_repayment_schedules.loan_id", "loans.id")
            ->leftJoin("loan_products", "loan_products.id", "loans.loan_product_id")
            ->leftJoin("branches", "branches.id", "loans.branch_id")
            ->leftJoin("users", "users.id", "loans.loan_officer_id")
            ->selectRaw("concat(clients.first_name,' ',clients.last_name) client,clients.mobile,clients.account_number,concat(users.first_name,' ',users.last_name) loan_officer,
            loans.id,loans.client_id,loans.approved_amount,loans.principal,loans.disbursed_on_date,loans.expected_maturity_date,
            loan_products.name loan_product,loans.status,loans.interest_disbursed_derived,loans.first_payment_date,loans.loan_term,loans.interest_rate,branches.name branch, SUM(loan_repayment_schedules.principal) total_principal,
            SUM(loan_repayment_schedules.total_due) balance,
            (SELECT DATEDIFF(CURDATE(), MIN(due_date))
                    FROM loan_repayment_schedules
                    WHERE loan_id = loans.id
                    AND due_date < CURDATE()
                    AND total_due > 0
                    AND paid_by_date IS NULL) AS days_in_arrears,
            (SELECT SUM(loan_repayment_schedules.total_due)
                    FROM loan_repayment_schedules
                    WHERE loan_id = loans.id
                    AND due_date < CURDATE()
                    AND total_due > 0) AS arrears")
            ->groupBy('loans.id', 'clients.first_name', 'clients.last_name', 'clients.mobile', 'clients.account_number', 
                      'users.first_name', 'users.last_name', 'loans.client_id', 'loans.approved_amount', 'loans.principal', 
                      'loans.disbursed_on_date', 'loans.expected_maturity_date', 'loan_products.name', 'loans.status','loans.first_payment_date','loans.loan_term','loans.interest_rate', 
                      'loans.interest_disbursed_derived', 'branches.name')
            ->first();
            

            if (!$loanData) {
                return response()->json(['message' => 'Loan not found'], 404);
            }

            return response()->json(['loan' => $loanData]);
        } catch (Exception $e) {
            Log::error('Error fetching loan details : ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan details'], 500);
        }
    }

    public function repaymentSchedules(Request $request, $id)
    {
        try {
            $loan = Loan::find($id);

            if (!$loan) {
                return response()->json(['message' => 'Loan not found'], 404);
            }

            $repaymentSchedules = $loan->repayment_schedules;

            return response()->json(['repayment_schedules' => $repaymentSchedules]);
        } catch (Exception $e) {
            Log::error('Error fetching loan repayment schedules : ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan repayment schedules'], 500);
        }
    }

    public function transactions(Request $request, $id)
    {
        try {
            $loan = Loan::find($id);

            if (!$loan) {
                return response()->json(['message' => 'Loan not found'], 404);
            }

            $transactions = $loan->transactions()->paginate($request->query('per_page', 15));

            return response()->json(['transactions' => $transactions]);
        } catch (Exception $e) {
            Log::error('Error fetching loan transactions : ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan transactions'], 500);
        }
    }

    public function guarantors(Request $request, $id)
    {
        try {
            $loan = Loan::find($id);

            if (!$loan) {
                return response()->json(['message' => 'Loan not found'], 404);
            }

            $guarantors = $loan->guarantors;

            return response()->json(['guarantors' => $guarantors]);
        } catch (Exception $e) {
            Log::error('Error fetching loan guarantors : ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching loan guarantors'], 500);
        }
    }

}
