<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioProjectImage extends Model
{
    use HasFactory;

    protected $fillable = ['portfolio_project_id', 'path', 'alt_text', 'caption', 'sort_order', 'is_cover'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_cover' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(function (PortfolioProjectImage $image): void {
            if ($image->is_cover) {
                static::query()
                    ->where('portfolio_project_id', $image->portfolio_project_id)
                    ->whereKeyNot($image->getKey())
                    ->update(['is_cover' => false]);
            }
        });
    }

    /** @return BelongsTo<PortfolioProject, $this> */
    public function portfolioProject(): BelongsTo
    {
        return $this->belongsTo(PortfolioProject::class);
    }
}
