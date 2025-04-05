<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRelationship extends Model
{
    use HasFactory;
    protected $table='client_relationships';
    public $timestamps=false;
    protected $fillable = ['name'];
}
