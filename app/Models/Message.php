<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'from_id',
        'to_id',
        'group_id',
        'is_read',
        'file_path', 
        'voice_message_path'
    ];

    public function from()
    {
        return $this->belongsTo(User::class, 'from_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public function to()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
