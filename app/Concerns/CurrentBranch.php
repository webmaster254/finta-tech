<?php

namespace App\Concerns;


use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait CurrentBranch
{


    public function branch(): BelongsTo
    {
        return $this->belongsTo(Filament::getTenant()->id, 'branch_id');
    }
}
