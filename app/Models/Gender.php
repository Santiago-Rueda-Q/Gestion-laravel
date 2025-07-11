<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use app\Models\User;

class Gender extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name'
        ];

    public function users() {
        return $this->hasMany(User::class);
    }
}
