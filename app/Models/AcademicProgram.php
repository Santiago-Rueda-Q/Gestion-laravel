<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use app\Models\User;
use app\Models\Institution;

class AcademicProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'description',
        'institution_id'
    ];

    public function institution() {
        return $this->belongsTo(Institution::class);
    }

    public function users() {
        return $this->hasMany(User::class);
    }
}
