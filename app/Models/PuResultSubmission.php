<?php

namespace App\Models;

use App\Enums\AspirantOffice;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuResultSubmission extends Model
{
    use HasUuids;

    protected $fillable = ['pu_id', 'office', 'video_path', 'video_name', 'sheets', 'remarks', 'submitted_by'];

    protected function casts(): array
    {
        return ['sheets' => 'array', 'office' => AspirantOffice::class];
    }

    public function pu(): BelongsTo
    {
        return $this->belongsTo(Pu::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
