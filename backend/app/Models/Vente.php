<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vente extends Model
{
    protected $table = 'ventes';

    protected $fillable = [
        'external_id', 'title', 'description',
        'property_type', 'is_new_property',
        'price', 'price_per_sqm', 'price_has_decreased', 'reduced_vat',
        'surface_area', 'rooms_quantity', 'surface_per_room',
        'interior_features', 'exterior_features', 'other_features', 'equipment_score',
        'city', 'postal_code', 'code_commune', 'department_code', 'code_region',
        'district_name', 'code_insee', 'latitude', 'longitude',
        'owner_type', 'owner_name', 'is_pro',
        'photos', 'photos_count',
        'publication_date', 'modification_date', 'delivery_date',
        'scraped_at', 'cleaned_at',
    ];

    protected $casts = [
        'price'               => 'integer',
        'price_per_sqm'       => 'decimal:2',
        'surface_area'        => 'decimal:2',
        'rooms_quantity'      => 'integer',
        'equipment_score'     => 'integer',
        'is_new_property'     => 'boolean',
        'price_has_decreased' => 'boolean',
        'reduced_vat'         => 'boolean',
        'is_pro'              => 'boolean',
        'interior_features'   => 'array',
        'exterior_features'   => 'array',
        'other_features'      => 'array',
        'photos'              => 'array',
        'publication_date'    => 'datetime',
        'modification_date'   => 'datetime',
        'delivery_date'       => 'date',
        'scraped_at'          => 'datetime',
        'cleaned_at'          => 'datetime',
    ];

    // ── Relationships ──

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'code_commune', 'code_commune');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'department_code', 'code_departement');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'code_region', 'code_region');
    }

    // ── Scopes ──

    public function scopeInCity($q, string $city)
    {
        return $q->where('city', $city);
    }

    public function scopeInDepartment($q, string $code)
    {
        return $q->where('department_code', $code);
    }

    public function scopePriceBetween($q, int $min, int $max)
    {
        return $q->whereBetween('price', [$min, $max]);
    }

    public function scopeOfType($q, string $type)
    {
        return $q->where('property_type', $type);
    }
}