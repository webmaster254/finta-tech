<?php

namespace App\Policies\Loan;

use App\Models\User;
use App\Models\Loan\Loan;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_loan');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Loan\Loan  $loan
     * @return bool
     */
    public function view(User $user, Loan $loan): bool
    {
        return $user->can('view_loan');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('create_loan');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Loan\Loan  $loan
     * @return bool
     */
    public function update(User $user, Loan $loan): bool
    {
        return $user->can('update_loan');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Loan\Loan  $loan
     * @return bool
     */
    public function delete(User $user, Loan $loan): bool
    {
        return $user->can('delete_loan');
    }

    /**
     * Determine whether the user can bulk delete.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_loan');
    }

    /**
     * Determine whether the user can permanently delete.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Loan\Loan  $loan
     * @return bool
     */
    public function forceDelete(User $user, Loan $loan): bool
    {
        return $user->can('{{ ForceDelete }}');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('{{ ForceDeleteAny }}');
    }

    /**
     * Determine whether the user can restore.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Loan\Loan  $loan
     * @return bool
     */
    public function restore(User $user, Loan $loan): bool
    {
        return $user->can('{{ Restore }}');
    }

    /**
     * Determine whether the user can bulk restore.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('{{ RestoreAny }}');
    }

    /**
     * Determine whether the user can replicate.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Loan\Loan  $loan
     * @return bool
     */
    public function replicate(User $user, Loan $loan): bool
    {
        return $user->can('{{ Replicate }}');
    }

    /**
     * Determine whether the user can reorder.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function reorder(User $user): bool
    {
        return $user->can('{{ Reorder }}');
    }


    public function canBeApprovedBy(User $user, Loan $loan): bool
    {
        return $user->can('approve_loan');
    }

    public function canBeRejectedBy(User $user, Loan $loan): bool
    {
        return $user->can('reject_loan');
    }

    public function canBeDisbursedBy(User $user, Loan $loan): bool
    {
        return $user->can('disburse_loan');
    }

    public function canBeClosedBy(User $user, Loan $loan): bool
    {
        return $user->can('close_loan');
    }

    public function canBeRepayedBy(User $user, Loan $loan): bool
    {
        return $user->can('repay_loan');
    }

    public function canChangeLoanOfficer(User $user, Loan $loan): bool
    {
        return $user->can('change_loan_officer_loan');
    }

    public function canAddCharges(User $user, Loan $loan): bool
    {
        return $user->can('add_charge_loan');
    }

}
