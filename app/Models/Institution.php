<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use app\Models\User;
use App\Models\Models\AcademicProgram;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
    'uuid',
    'name',
    'acronym',
    'city',
    'country'];

    public function programs() {
        return $this->hasMany(AcademicProgram::class);
    }

    public function users() {
        return $this->hasMany(User::class);
    }
}
