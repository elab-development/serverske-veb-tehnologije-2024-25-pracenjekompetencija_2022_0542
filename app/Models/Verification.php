<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = ['credential_id', 'status', 'notes'];

    public function credential()
    {
        return $this->belongsTo(Credential::class);
    }
}
