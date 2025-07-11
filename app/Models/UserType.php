<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use app\Models\User;

class UserType extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'description'
        ];

    public function users() {
        return $this->hasMany(User::class);
    }
}
