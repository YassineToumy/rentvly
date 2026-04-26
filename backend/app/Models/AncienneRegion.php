<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AncienneRegion extends Model
{
    protected $table = 'anciennes_regions';
    protected $primaryKey = 'code_ancienne_region';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false; // only has created_at

    protected $fillable = ['code_ancienne_region', 'nom', 'code_region'];

    public function region()
    {
        return $this->belongsTo(Region::class, 'code_region', 'code_region');
    }
}