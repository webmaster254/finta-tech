<?php

namespace App\Policies;

use App\Models\User;
use App\Filament\Pages\Settings\Settings;
use Illuminate\Auth\Access\HandlesAuthorization;

class SettingsPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_settings');
    }

    public function view(User $user, Settings $settings): bool
    {
        return $user->can('view_settings');
    }
}
