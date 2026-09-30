<?php

namespace Modules\AIAssistant\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AISkillRun extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_skill_runs';

    /**
     * Disable the updated_at timestamp since logs are append-only.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'skill_key',
        'input',
        'output_summary',
        'execution_ms',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input' => 'array',
        'execution_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that executed the skill.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
