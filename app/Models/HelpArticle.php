<?php

/*
[Modulo: app/Models]
@Author: André Gomes ( @acidcode )
@since 2026-09-04
Conteudo da central de ajuda nos tres formatos que o portal oferece: duvida, guia e video.
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpArticle extends Model
{
    use HasFactory;

    public const TYPE_FAQ = 'faq';

    public const TYPE_GUIDE = 'guide';

    public const TYPE_VIDEO = 'video';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'help_category_id',
        'type',
        'title',
        'slug',
        'excerpt',
        'body',
        'video_url',
        'file_path',
        'position',
        'is_published',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /** @return BelongsTo<HelpCategory, $this> */
    public function helpCategory(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class);
    }
}
