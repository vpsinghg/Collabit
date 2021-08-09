<?php

namespace App\Models;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Task extends Model
{
    //
    use SoftDeletes;

    public function user(){
        return $this->belongsTo(User::class);
    }
    protected $table    =   'tasks';
    protected $fillable =   ['user_id','title',   'description',  'assignee', 'dueDate',  'status',];
    protected $hidden = [];
}
