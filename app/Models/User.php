<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\DocumentType;
use App\Models\UserType;
use App\Models\Institution;
use App\Models\AcademicProgram;
use App\Models\Gender;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'birthdate',
        'profile_photo',
        'document_type_id',
        'user_type_id',
        'document_number',
        'institution_id',
        'academic_program_id',
        'gender_id',
        'company_name',
        'company_address',
        'status',
        'accepted_terms',
        'email_verified_at',
        'password',
        'remember_token',
    ];

    public function documentType() {
        return $this->belongsTo(DocumentType::class);
    }

    public function userType() {
        return $this->belongsTo(UserType::class);
    }

    public function institution() {
        return $this->belongsTo(Institution::class);
    }

    public function academicProgram() {
        return $this->belongsTo(AcademicProgram::class);
    }

    public function gender() {
        return $this->belongsTo(Gender::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
