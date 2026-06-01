<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'otp_code', 'otp_expires_at', 'estado'])]
#[Hidden(['password', 'remember_token'])]
#[Appends(['role', 'permisos'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getRoleAttribute()
    {
        return $this->getRoleNames()->first();
    }

    public function getPermisosAttribute()
    {
        return $this->getAllPermissions()->pluck('name');
    }

    public function dispositivos()
    {
        return $this->hasMany(DispositivoConocido::class);
    }

    public function estacionActual()
    {
        return $this->hasOne(Estacion::class, 'estudiante_actual_id');
    }

    public function perfil()
    {
        return $this->hasOne(Perfil::class, 'user_id');
    }

    public function grupos()
    {
        return $this->belongsToMany(Grupo::class, 'grupo_user');
    }

    public function horariosDocente()
    {
        return $this->hasMany(Horario::class, 'docente_id');
    }

    public function logsComandos()
    {
        return $this->hasMany(LogsComando::class, 'usuario_id');
    }

    public function biometriaVocal()
    {
        return $this->hasOne(BiometriaVocal::class, 'usuario_id');
    }

    public function biometriaFacial()
    {
        return $this->hasOne(BiometriaFacial::class, 'user_id');
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            if ($user->perfil) {
                $user->perfil()->delete();
            }
            if ($user->biometriaVocal) {
                $user->biometriaVocal()->delete();
            }
            if ($user->biometriaFacial) {
                $user->biometriaFacial()->delete();
            }
            $user->dispositivos()->delete();
            $user->horariosDocente()->delete();
        });

        static::restoring(function ($user) {
            $user->perfil()->withTrashed()->restore();
            $user->dispositivos()->withTrashed()->restore();
            $user->biometriaVocal()->withTrashed()->restore();
            $user->biometriaFacial()->withTrashed()->restore();
            $user->horariosDocente()->withTrashed()->restore();
        });
    }
}
