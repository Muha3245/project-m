<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(GroupMessage::class, 'group_id');
    }

    public function unreadMessages()
    {
        return $this->hasMany(GroupMessage::class, 'group_id')->whereNull('is_read');
    }

    public function scopeSearch($query, $term)
    {
        if ($term) {
            $query->where('name', 'LIKE', '%' . $term . '%');
        }
        return $query;
    }
}
