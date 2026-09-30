<?php

namespace Modules\Restaurant\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Floors extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */  
    protected $table = 'floors';  
    protected $fillable = ['name','floorplan','warehouse_id','status'];

    public function warehouse()
    {
        return $this->belongsTo(\App\Models\Warehouse::class);
    }
}
