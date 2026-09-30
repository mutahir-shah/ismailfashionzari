<?php

namespace Modules\AIAssistant\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class AIConversation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_conversations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'provider',
        'mode',
        'title',
    ];

    /**
     * Get the user that owns the conversation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the messages for the conversation.
     * Messages are returned deterministically in chronological order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AIMessage::class, 'conversation_id');
    }

    /**
     * Scope a query to only include conversations for a specific user and tenant.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @param string|null $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUserAndTenant($query, int $userId, ?string $tenantId = null)
    {
        return $query->where('user_id', $userId)
                     ->when($tenantId !== null, function ($q) use ($tenantId) {
                         return $q->where('tenant_id', $tenantId);
                     }, function ($q) {
                         return $q->whereNull('tenant_id');
                     });
    }
}
