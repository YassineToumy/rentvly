<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departement extends Model
{
    protected $table = 'departements';
    protected $primaryKey = 'code_departement';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code_departement', 'nom', 'code_region'];

    public function region()
    {
        return $this->belongsTo(Region::class, 'code_region', 'code_region');
    }

    public function communes()
    {
        return $this->hasMany(Commune::class, 'code_departement', 'code_departement');
    }
}