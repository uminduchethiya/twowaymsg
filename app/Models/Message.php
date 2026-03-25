<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['user_id', 'receiver_id', 'room', 'body'];

    protected $with = ['user'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope: all DMs between two users (in either direction).
     */
    public function scopeConversation($query, int $userA, int $userB)
    {
        return $query->whereNotNull('receiver_id')
            ->where(function ($q) use ($userA, $userB) {
                $q->where(function ($q) use ($userA, $userB) {
                    $q->where('user_id', $userA)->where('receiver_id', $userB);
                })->orWhere(function ($q) use ($userA, $userB) {
                    $q->where('user_id', $userB)->where('receiver_id', $userA);
                });
            });
    }
}
