<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuTaskAudit extends Model
{
    use HasUuids;

    protected $fillable = ['pu_id', 'office', 'action', 'details', 'performed_by'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function pu(): BelongsTo
    {
        return $this->belongsTo(Pu::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
