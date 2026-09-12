<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TabContext extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isStale(int $idleMinutes = 30): bool
    {
        return $this->last_seen_at && $this->last_seen_at->subMinutes($idleMinutes)->isPast();
    }

    public function touchLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function hashUserAgent(?string $userAgent): ?string
    {
        return $userAgent ? hash('sha256', $userAgent) : null;
    }

    public static function createForUser(int $userId, ?string $userAgent = null, int $ttlMinutes = 120): array
    {
        $token = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');

        $context = static::create([
            'token_hash' => static::hashToken($token),
            'user_id' => $userId,
            'user_agent_hash' => static::hashUserAgent($userAgent),
            'last_seen_at' => now(),
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return ['token' => $token, 'context' => $context];
    }

    public static function resolve(string $token, ?string $userAgent = null): ?self
    {
        $hash = static::hashToken($token);

        $context = static::where('token_hash', $hash)->first();

        if (!$context) {
            return null;
        }

        if ($context->isExpired()) {
            $context->delete();
            return null;
        }

        if ($context->user_agent_hash && $userAgent && $context->user_agent_hash !== static::hashUserAgent($userAgent)) {
            return null;
        }

        $context->touchLastSeen();

        return $context;
    }

    public static function purgeExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }

    public static function revokeAllForUser(int $userId): int
    {
        return static::where('user_id', $userId)->delete();
    }
}
