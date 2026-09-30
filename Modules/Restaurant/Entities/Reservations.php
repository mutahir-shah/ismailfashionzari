<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Reservations extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */  
    protected $table = 'reservation';  
    protected $fillable = ['name','phone','email','date','time','person','table_id','status'];
}
