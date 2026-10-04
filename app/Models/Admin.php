<?php

namespace App\Models;

use App\Traits\DocumentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasRoles, Notifiable, HasFactory, DocumentTrait, LogsActivity;

    public const DEVELOPER_ROLE = 'Developer';

    protected $guard_name = 'admin';

    protected $fillable = ['name', 'email', 'role_id', 'status', 'password'];

    protected $hidden = ['password', 'remember_token'];

    public $with = ['role'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isDeveloper(): bool
    {
        return optional($this->role)->name === self::DEVELOPER_ROLE;
    }

    public function setPasswordAttribute($value)
    {
        if (!is_null($value)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role_id', 'status'])
            ->logOnlyDirty();
    }
}
