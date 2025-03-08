<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subtask extends Model
{
    use HasFactory;
    const ROLE_ADMIN = 'task_admin';
    const ROLE_MEMBER = 'task_member';
    protected $fillable = [
        'task_id',
        'title',
        'status',
        'description',
        'type',
        'priority',
        'sorting',
        'due_date',
    ];
    public function task()
    {
        return $this->belongsTo(Task::class,'task_id');
    }
    public function board()
    {
        return $this->belongsTo(Board::class, 'board_id');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public function history()
    {
        return $this->hasMany(TaskHistory::class, 'task_id')->latest();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'subtask_users', 'subtask_id', 'user_id',)->withPivot('role')->withTimestamps();
    }
    // public function subtasks()
    // {
    //     return $this->hasMany(Subtask::class);
    // }


    // public function subtask()
    // {
    //     return $this->belongsTo(subtask::class,'task_id');
    // }
}
