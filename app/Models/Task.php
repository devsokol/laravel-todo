<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = ['status', 'priority', 'title', 'description', 'user_id'];

    public function parentTasks()
    {
        return $this->belongsToMany(Task::class, 'task_relationships', 'descendant', 'ancestor')
            ->withPivot('depth')
            ->withTimestamps();
    }

    public function childTasks()
    {
        return $this->belongsToMany(Task::class, 'task_relationships', 'ancestor', 'descendant')
            ->withPivot('depth')
            ->withTimestamps();
    }

    public function getNestedChildren()
    {
        return $this->childTasks()->with('getNestedChildren');
    }
}
