<?php

namespace App\Observers;

use App\Models\Client;
use Illuminate\Support\Facades\Storage;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     */
    public function created(Client $client): void
    {
        //
    }

    // public function saved(Client $client): void
    // {


    //     if ($client->isDirty('photo') && is_null($client->photo)){
    //         Storage::disk('public')->delete($client->getOriginal('photo'));
    //     }
    // }

    /**
     * Handle the Client "updated" event.
     */
    public function updated(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "deleted" event.
     */
    // public function deleted(Client $client): void
    // {
    //     if ($client->isDirty('photo') &&! is_null($client->photo)) {
    //         Storage::disk('public')->delete($client->photo);
    //     }
    // }

    /**
     * Handle the Client "restored" event.
     */
    public function restored(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "force deleted" event.
     */
    public function forceDeleted(Client $client): void
    {
        //
    }
}
