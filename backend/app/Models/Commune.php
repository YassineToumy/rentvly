<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    protected $table = 'communes';
    protected $primaryKey = 'code_commune';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code_commune', 'nom', 'code_departement', 'code_region', 'siren', 'code_epci', 'population'];

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'code_departement', 'code_departement');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'code_region', 'code_region');
    }
}