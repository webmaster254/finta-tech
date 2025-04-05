<?php

namespace App\Http\Controllers\v1;

use Log;
use Exception;
use App\Models\Client;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ClientsController extends Controller
{

    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15); // Get items per page from query or default to 15

        // Check if loan_officer_id is provided
        if (!$request->has('loan_officer_id')) {
            return response()->json(['message' => 'Loan officer ID is required'], 400);
        }

        $user = $request->loan_officer_id;

        try{
            $clients = Client::where('loan_officer_id', $user)->orderBy("created_at","desc");

            if ($search) {
                $clients->where(function ($query) use ($search) {
                    $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('middle_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('mobile', 'LIKE', "%{$search}%")
                    ->orWhere('account_number', 'LIKE', "%{$search}%");
                });
            }

            $clients = $clients->paginate($perPage);

            // Check if any repayment schedule data is found
            if ($clients->isEmpty()) {
                return response()->json(['message' => 'No clients  found'], 404);
            }

            return response()->json(
                [
                    'clients' => $clients
                ]);

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Error fetching clients : ' . $e->getMessage());

            return response()->json(['message' => 'An error occurred while fetching data'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $client = Client::find($id);

            if (!$client) {
                return response()->json(['message' => 'Client not found'], 404);
            }

            return response()->json(['client' => $client]);
        } catch (Exception $e) {
            Log::error('Error fetching client details : ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching client details'], 500);
        }
    }

    public function loans(Request $request, $id)
    {

        try {
            $client = Client::find($id);


            if (!$client) {
                return response()->json(['message' => 'Client not found'], 404);
            }

            $perPage = $request->query('per_page', 15); // Get items per page from query or default to 15
            $loans = $client->loans()->paginate($perPage);

            return response()->json(['loans' => $loans]);
        } catch (Exception $e) {
            Log::error('Error fetching client loans : ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching client loans'], 500);
        }
    }


    public function searchClients(Request $request)
    {
        try {
            $search = $request->query('search');
            $perPage = $request->query('per_page', 15); // Get items per page from query or default to 15

            $clients = Client::where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('middle_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhere('mobile', 'LIKE', "%{$search}%")
                    ->orWhere('account_number', 'LIKE', "%{$search}%");
            })->paginate($perPage);

            return response()->json(['clients' => $clients]);
        } catch (Exception $e) {
            Log::error('Error fetching search results : ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while fetching search results'], 500);
        }
    }


}
