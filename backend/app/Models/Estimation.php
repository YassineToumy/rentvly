<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estimation extends Model
{
    protected $fillable = [
        'user_id',
        'listing_id',
        'parent_id',
        'city',
        'postal_code',
        'district_name',
        'property_type',
        'surface_area',
        'rooms',
        'predicted_rent',
        'purchase_price',
        'net_yield',
        'is_purchased',
        'purchased_at',
        'form_data',
        'prediction',
        'rentability',
    ];

    protected function casts(): array
    {
        return [
            'predicted_rent' => 'float',
            'purchase_price' => 'float',
            'net_yield' => 'float',
            'is_purchased' => 'boolean',
            'purchased_at' => 'datetime',
            'form_data' => 'array',
            'prediction' => 'array',
            'rentability' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->latest();
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function latestPurchasePrice(): ?float
    {
        $latestVariant = $this->relationLoaded('variants')
            ? $this->variants->first()
            : $this->variants()->first();

        if ($latestVariant?->purchase_price) {
            return (float) $latestVariant->purchase_price;
        }

        return $this->purchase_price !== null ? (float) $this->purchase_price : null;
    }
}
