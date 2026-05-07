<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $connection = 'mysql';
    protected $table     = 'activity_logs';

    protected $fillable = [
        'user_id',
        'username',
        'user_name',
        'user_role',
        'ip_address',
        'method',
        'url',
        'route_name',
        'user_agent',
        'status_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeByUser($query, string $username)
    {
        return $query->where('username', 'like', "%{$username}%");
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('user_role', $role);
    }

    public function scopeByDate($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [
            $from . ' 00:00:00',
            $to   . ' 23:59:59',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Label warna badge untuk HTTP method.
     */
    public function getMethodBadgeAttribute(): string
    {
        return match (strtoupper($this->method)) {
            'GET'    => 'success',
            'POST'   => 'primary',
            'PUT',
            'PATCH'  => 'warning',
            'DELETE' => 'danger',
            default  => 'secondary',
        };
    }
}
