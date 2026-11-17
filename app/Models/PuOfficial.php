<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuOfficial extends Model
{
    use HasUuids;

    protected $fillable = ['pu_id', 'po_name', 'apo1_name', 'apo2_name', 'security_name', 'updated_by'];

    public function pu(): BelongsTo
    {
        return $this->belongsTo(Pu::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
