<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardPoint extends Model
{
    use HasFactory;
    protected $table = 'reward_points';
     protected $fillable = [
        'customer_id',
        'reward_point_type',
        'points',
        'deducted_points',
        'note',
        'expired_at',
        'created_by',
        'updated_by',
        'sale_id',
    ];

    public function customer(){
        return $this->belongsTo(Customer::class,'customer_id');
    }

    public function user(){
        return $this->belongsTo(User::class,'created_by');
    }

    protected static function boot(){
        parent::boot();
        static::creating(function ($query){
            $query->created_by = auth()->id();
        });
    }
}
