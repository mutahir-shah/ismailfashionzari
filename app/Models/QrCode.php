<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'qrable_id',
        'qrable_type',
        'url',
        'path',
        'code',
        'meta',
        'is_active',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Boot: auto-generate unique code if not provided
     */
    protected static function booted()
    {
        static::creating(function ($qr) {
            if (empty($qr->code)) {
                $qr->code = (string) Str::uuid();
            }
        });
    }

    /**
     * Polymorphic relation
     */
    public function qrable()
    {
        return $this->morphTo();
    }

    /**
     * Scope: active QR codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Accessor: full URL for QR image
     */
    public function getImageUrlAttribute()
    {
        return $this->path ? asset('storage/' . $this->path) : null;
    }

    /**
     * Helper: regenerate QR (you can expand later)
     */
    public function regenerate($generatorCallback)
    {
        // $generatorCallback should return ['path' => ..., 'url' => ...]
        $data = $generatorCallback($this);

        $this->update([
            'path' => $data['path'] ?? $this->path,
            'url'  => $data['url'] ?? $this->url,
        ]);

        return $this;
    }

    /**
     * Helper: resolve route URL (if using /q/{code})
     */
    public function getRedirectUrl()
    {
        return $this->url;
    }
}