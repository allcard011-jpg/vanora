<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'brand_id', 'category_id', 'name', 'slug', 'description',
        'top_notes', 'middle_notes', 'base_notes',
        'image_path', 'is_active', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** Produk aktif yang punya minimal satu varian aktif dengan stok. */
    /** Tampil di katalog: produk aktif, kategori aktif, minimal satu varian aktif dengan stok. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('category', fn (Builder $c) => $c->where('is_active', true))
            ->whereHas('variants', fn (Builder $v) => $v->where('is_active', true)->where('stock', '>', 0));
    }

    /** Menambahkan kolom min_price: harga terendah dari varian yang bisa dibeli. */
    public function scopeWithStartingPrice(Builder $query): Builder
    {
        return $query->withMin(
            ['variants as min_price' => fn ($v) => $v->where('is_active', true)->where('stock', '>', 0)],
            'price'
        );
    }

    /** URL gambar; null jika belum ada (tampilan memakai placeholder). */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->image_path) {
                return null;
            }

            return str_starts_with($this->image_path, 'http')
                ? $this->image_path
                : asset($this->image_path);
        });
    }
}
