<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request)
    {
        $query = User::query();

           // Apply filters based on query parameters
           if ($request->has('first_name')) {
               $query->where('first_name', 'like', '%' . $request->query('first_name') . '%');
           }

           if ($request->has('email')) {
               $query->where('email', 'like', '%' . $request->query('email') . '%');
           }

           // Add more filters as needed
           // if ($request->has('another_field')) {
           //     $query->where('another_field', $request->query('another_field'));
           // }
          $users = $query->get();

           return response()->json(['user' => $users]);
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
