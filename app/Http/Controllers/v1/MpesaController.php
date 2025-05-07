<?php

namespace App\Http\Controllers\v1;

use Log;
use Exception;
use App\Models\MpesaC2B;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MpesaController extends Controller
{

    public function index(Request $request)
    {


        $search = $request->query('search');
        $date = $request->date;
        $perPage = $request->query('per_page', 15); // Get items per page from query or default to 15

        try{
            $mpesaData = MpesaC2B::whereBetween('created_at', [now()->subMonths(2), now()])
                           ->orderBy("created_at","desc");
            if($search)
            {
                $mpesaData->where(function ($query) use ($search) {
                    $query->where('Transaction_ID', 'LIKE', "%{$search}%")

                    ->orWhere('FirstName', 'LIKE', "%{$search}%")
                    ->orWhere('status', 'LIKE', "%{$search}%")
                    ->orWhere('Account_Number', 'LIKE', "%{$search}%");
                });
            }

            if($date)
            {
                $mpesaData->whereDate('created_at', '=', $date);
            }

            $mpesaData= $mpesaData->paginate($perPage);
                // Check if any repayment schedule data is found
                if ($mpesaData->isEmpty()) {
                    return response()->json(['message' => 'No Mpesa record found'], 404);
                }

                return response()->json(
                    [
                        'mpesa' => $mpesaData
                    ]);

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching mpesa Record : ' . $e->getMessage());

            return response()->json(['message' => 'An error occurred while fetching data'], 500);
        }
    }



}
