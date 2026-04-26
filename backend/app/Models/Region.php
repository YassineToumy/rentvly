<?php
// ── app/Models/Region.php ──

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'regions';
    protected $primaryKey = 'code_region';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code_region', 'nom'];

    public function departments()
    {
        return $this->hasMany(Department::class, 'code_region', 'code_region');
    }
}