<?php

namespace App\Observers;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Enums\ChartAccountType;
use App\Enums\ChartAccountCategory;
use App\Models\ChartOfAccountSubtype;
use App\Utilities\Accounting\AccountCode;

class ChartOfAccountObserver
{


    public function updating(ChartOfAccount $account): void
    {
        if ($account->isDirty('subtype_id')) {
            $this->setCategoryAndType($account, false);
        }
    }

    private function setCategoryAndType(ChartOfAccount $account, bool $isCreating): void
    {
        $subtype = $account->subtype_id ? ChartOfAccountSubtype::find($account->subtype_id) : null;

        if ($subtype) {
            $account->category = $subtype->category;
            $account->account_type = $subtype->type;
        } elseif ($isCreating) {
            $account->category = ChartAccountCategory::Asset;
            $account->account_type = ChartAccountType::CurrentAsset;
        }
    }

    private function setFieldsForBankAccount(ChartOfAccount $account): void
    {
        $generatedAccountCode = AccountCode::generate($account->subtype);

        $account->gl_code = $generatedAccountCode;

        $account->save();
    }

    /**
     * Handle the Account "created" event.
     */
    public function created(ChartOfAccount $account): void
    {
        if (($account->accountable_type === BankAccount::class) && $account->code === null) {
            $this->setFieldsForBankAccount($account);
        }


    }
}
