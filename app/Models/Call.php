<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'caller_id',
        'receiver_id',
        'call_type',
        'status',
    ];

    /**
     * Get the caller user details.
     */
    public function caller()
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    /**
     * Get the receiver user details.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}

