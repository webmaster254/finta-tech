<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\Client;
use App\Models\Loan\Loan;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use Filament\Facades\Filament;
use App\Models\ChartOfAccountSubtype;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

class ApplyTenantScopes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        Client::addGlobalScope(
            fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );
        // User::addGlobalScope(
        //     fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        // );
        Loan::addGlobalScope(
            fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );
        BankAccount::addGlobalScope(
            fn(Builder $query) => $query-> whereBelongsTo(Filament::getTenant()),
        );
        ChartOfAccount::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );
        ChartOfAccountSubtype::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );
        Transaction::addGlobalScope(
            fn(Builder $query) => $query->whereBelongsTo(Filament::getTenant()),
        );


        return $next($request);

    }
}
