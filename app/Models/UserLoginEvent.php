<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginEvent extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'email', 'ip_address', 'user_agent', 'browser', 'platform', 'device', 'logged_in_at'];

    protected $casts = ['logged_in_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
