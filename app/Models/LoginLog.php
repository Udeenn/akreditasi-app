<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'login_type',
        'ip_address',
        'user_agent',
        'status',
        'message',
        'logged_in_at',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
    ];

    /**
     * Get the user that owns the login log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a successful login
     */
    public static function logSuccess(int $userId, string $username, string $type = 'cas'): self
    {
        return self::create([
            'user_id' => $userId,
            'username' => $username,
            'login_type' => $type,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'success',
            'logged_in_at' => now(),
        ]);
    }

    /**
     * Log a failed login attempt
     */
    public static function logFailed(string $username, string $message, string $type = 'cas'): self
    {
        return self::create([
            'user_id' => null,
            'username' => $username,
            'login_type' => $type,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'failed',
            'message' => $message,
            'logged_in_at' => now(),
        ]);
    }
}
