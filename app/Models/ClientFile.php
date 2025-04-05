<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_id',
        'client_id',
        'name',
        'description',
        'link',
    ];
    protected $table = 'client_files';
}
