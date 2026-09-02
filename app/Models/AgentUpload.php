<?php

namespace App\Models;

use Database\Factories\AgentUploadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentUpload extends Model
{
    /** @use HasFactory<AgentUploadFactory> */
    use HasFactory;

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'original_name',
        'mime_type',
        'size_bytes',
        'status',
        'local_path',
        'stored_disk',
        'stored_path',
        'processed_at',
        'last_checked_at',
        'error_message',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'processed_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
