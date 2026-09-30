<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    use \App\Traits\WarehouseScoped;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public static function capability_profiles()
    {
        $profiles = [
            'default' => __('db.Default'),
            'simple' => __('db.Simple'),
            'SP2000' => __('db.Star SP2000 Series'),
            'TEP-200M' => __('db.EPOS TEP200M Series'),
            'TM-U220' => __('db.Epson TM-U220'),
            'RP326' => __('db.Rongta RP326'),
            'P822D' => __('db.PBM P822D'),
        ];

        return $profiles;
    }

    public function getCapabilityProfileStrAttribute()
    {
        $profiles = Printer::capability_profiles();
        return $profiles[$this->capability_profile] ?? $this->capability_profile;
    }

    public static function connection_types()
    {
        $types = [
            'network' => __('db.Network'),
            'windows' => __('db.Windows'),
            'linux' => __('db.Linux'),
        ];

        return $types;
    }

    public function getConnectionTypeStrAttribute()
    {
        $types = Printer::connection_types();
        return $types[$this->connection_type] ?? $this->connection_type;
    }

}
