<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Biblioteca extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'biblioteca';

    protected $fillable = [
        'titulo',
        'slug',
        'descripcion',
        'padre_id',
        'carpeta_id',
        'is_premium',
        'orden'
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'orden' => 'integer'
    ];

    // Relación con carpeta
    public function carpeta()
    {
        return $this->belongsTo(Carpeta::class, 'carpeta_id');
    }

    // Relación padre
    public function padre()
    {
        return $this->belongsTo(Biblioteca::class, 'padre_id');
    }

    // Relación hijos
    public function hijos()
    {
        return $this->hasMany(Biblioteca::class, 'padre_id')->orderBy('orden');
    }

    // Relación hijos recursiva (para obtener toda la jerarquía)
    public function hijosRecursivos()
    {
        return $this->hijos()->with('hijosRecursivos');
    }

    // Scope para elementos raíz (sin padre)
    public function scopeRaiz($query)
    {
        return $query->whereNull('padre_id');
    }

    // Scope para elementos premium
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    // Scope para elementos gratuitos
    public function scopeGratuito($query)
    {
        return $query->where('is_premium', false);
    }

    // Scope por carpeta
    public function scopePorCarpeta($query, $carpetaId)
    {
        return $query->where('carpeta_id', $carpetaId);
    }

    // Scope ordenados
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')->orderBy('titulo');
    }

    // Generar slug automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($biblioteca) {
            if (empty($biblioteca->slug)) {
                $biblioteca->slug = Str::slug($biblioteca->titulo);
            }
        });

        static::updating(function ($biblioteca) {
            if ($biblioteca->isDirty('titulo') && empty($biblioteca->slug)) {
                $biblioteca->slug = Str::slug($biblioteca->titulo);
            }
        });
    }

    // Obtener la ruta completa (breadcrumb)
    public function getRutaCompletaAttribute()
    {
        $ruta = collect([$this->titulo]);
        $padre = $this->padre;

        while ($padre) {
            $ruta->prepend($padre->titulo);
            $padre = $padre->padre;
        }

        return $ruta->implode(' > ');
    }

    // Verificar si tiene hijos
    public function tieneHijos()
    {
        return $this->hijos()->exists();
    }

    // Obtener nivel de profundidad
    public function getNivelAttribute()
    {
        $nivel = 0;
        $padre = $this->padre;

        while ($padre) {
            $nivel++;
            $padre = $padre->padre;
        }

        return $nivel;
    }
}