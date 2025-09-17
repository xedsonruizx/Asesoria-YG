<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Carpeta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'carpetas';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'color',
        'icono',
        'orden',
        'activa',
        'padre_id',
        'nivel',
        'ruta_completa'
    ];

    protected $casts = [
        'activa' => 'boolean',
        'orden' => 'integer',
        'nivel' => 'integer'
    ];

    // Relación con carpeta padre
    public function padre()
    {
        return $this->belongsTo(Carpeta::class, 'padre_id');
    }

    // Relación con subcarpetas
    public function subcarpetas()
    {
        return $this->hasMany(Carpeta::class, 'padre_id')->orderBy('orden');
    }

    // Relación subcarpetas recursiva (para obtener toda la jerarquía)
    public function subcarpetasRecursivas()
    {
        return $this->subcarpetas()->with([
            'subcarpetasRecursivas',
            'bibliotecas' => function ($query) {
                $query->whereNull('deleted_at')->orderBy('orden');
            }
        ]);
    }

    // Relación con elementos de biblioteca
    public function bibliotecas()
    {
        return $this->hasMany(Biblioteca::class, 'carpeta_id')->orderBy('orden');
    }

    // Contar elementos de biblioteca activos
    public function bibliotecasCount()
    {
        return $this->bibliotecas()->whereNull('deleted_at')->count();
    }

    // Contar subcarpetas
    public function subcarpetasCount()
    {
        return $this->subcarpetas()->count();
    }

    // Scope para carpetas raíz (sin padre)
    public function scopeRaiz($query)
    {
        return $query->whereNull('padre_id');
    }

    // Scope para carpetas activas
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    // Scope ordenadas
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    // Scope por nivel
    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    // Obtener todas las carpetas padre (breadcrumb)
    public function getRutaPadres()
    {
        $padres = collect();
        $carpeta = $this;
        
        while ($carpeta && $carpeta->padre) {
            $padres->prepend($carpeta->padre);
            $carpeta = $carpeta->padre;
        }
        
        return $padres;
    }

    // Obtener ruta completa como string
    public function getRutaCompletaAttribute()
    {
        if ($this->attributes['ruta_completa']) {
            return $this->attributes['ruta_completa'];
        }

        $padres = $this->getRutaPadres();
        $ruta = $padres->pluck('nombre')->push($this->nombre)->implode(' > ');
        
        return $ruta;
    }

    // Verificar si puede ser eliminada (no tiene subcarpetas ni bibliotecas)
    public function puedeSerEliminada()
    {
        return $this->subcarpetas()->count() === 0 && 
               $this->bibliotecas()->whereNull('deleted_at')->count() === 0;
    }

    // Generar slug automáticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($carpeta) {
            if (empty($carpeta->slug)) {
                $carpeta->slug = Str::slug($carpeta->nombre);
            }
            
            // Calcular nivel basado en el padre
            if ($carpeta->padre_id) {
                $padre = static::find($carpeta->padre_id);
                $carpeta->nivel = $padre ? $padre->nivel + 1 : 0;
            } else {
                $carpeta->nivel = 0;
            }
        });

        static::updating(function ($carpeta) {
            if ($carpeta->isDirty('nombre') && empty($carpeta->slug)) {
                $carpeta->slug = Str::slug($carpeta->nombre);
            }
            
            // Recalcular nivel si cambió el padre
            if ($carpeta->isDirty('padre_id')) {
                if ($carpeta->padre_id) {
                    $padre = static::find($carpeta->padre_id);
                    $carpeta->nivel = $padre ? $padre->nivel + 1 : 0;
                } else {
                    $carpeta->nivel = 0;
                }
            }
        });

        static::saved(function ($carpeta) {
            // Solo actualizar ruta completa si no se está actualizando ya la ruta_completa
            // Esto evita el bucle infinito
            if (!$carpeta->wasChanged('ruta_completa')) {
                // Actualizar ruta completa
                $padres = $carpeta->getRutaPadres();
                $ruta = $padres->pluck('nombre')->push($carpeta->nombre)->implode(' > ');
                
                // Usar DB::table para evitar disparar eventos
                DB::table('carpetas')
                    ->where('id', $carpeta->id)
                    ->update(['ruta_completa' => $ruta]);
            }
            
            // Actualizar niveles de subcarpetas si es necesario
            if ($carpeta->wasChanged('nivel')) {
                $carpeta->actualizarNivelesSubcarpetas();
            }
        });
    }

    // Actualizar ruta completa (método simplificado)
    public function updateRutaCompleta()
    {
        $padres = $this->getRutaPadres();
        $ruta = $padres->pluck('nombre')->push($this->nombre)->implode(' > ');
        
        // Usar DB::table para evitar disparar eventos
        \DB::table('carpetas')
            ->where('id', $this->id)
            ->update(['ruta_completa' => $ruta]);
            
        // Actualizar el atributo en memoria
        $this->ruta_completa = $ruta;
    }

    // Actualizar niveles de todas las subcarpetas recursivamente
    public function actualizarNivelesSubcarpetas()
    {
        $this->subcarpetas()->each(function ($subcarpeta) {
            $subcarpeta->update(['nivel' => $this->nivel + 1]);
            $subcarpeta->actualizarNivelesSubcarpetas();
        });
    }

    // Obtener carpeta por ID (cambiar para usar ID en lugar de slug)
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'id', $value)->firstOrFail();
    }

    // Obtener árbol jerárquico completo
    public static function getArbolJerarquico()
    {
        return static::with([
                'subcarpetasRecursivas.bibliotecas' => function ($query) {
                    $query->whereNull('deleted_at')->orderBy('orden');
                },
                'bibliotecas' => function ($query) {
                    $query->whereNull('deleted_at')->orderBy('orden');
                }
            ])
            ->raiz()
            ->activas()
            ->ordenadas()
            ->get();
    }

    /**
     * Eliminar carpeta de forma recursiva
     */
    public function eliminarRecursiva()
    {
        // Eliminar todas las subcarpetas recursivamente
        foreach ($this->subcarpetas as $subcarpeta) {
            $subcarpeta->eliminarRecursiva();
        }

        // Eliminar todos los elementos de biblioteca asociados
        $this->bibliotecas()->delete(); // Soft delete

        // Eliminar la carpeta
        $this->delete();
    }

    /**
     * Verificar si puede ser eliminada (versión mejorada)
     */
    // Obtener información sobre dependencias antes de eliminar
    public function getInfoEliminacion()
    {
        $subcarpetas = $this->subcarpetasRecursivas->count();
        $bibliotecas = $this->bibliotecasRecursivas()->whereNull('deleted_at')->count();
        
        return [
            'subcarpetas_count' => $subcarpetas,
            'bibliotecas_count' => $bibliotecas,
            'puede_eliminar' => true, // Ahora siempre se puede eliminar con confirmación
            'mensaje' => $this->getMensajeEliminacion($subcarpetas, $bibliotecas)
        ];
    }

    private function getMensajeEliminacion($subcarpetas, $bibliotecas)
    {
        if ($subcarpetas === 0 && $bibliotecas === 0) {
            return 'Esta carpeta está vacía y se puede eliminar sin problemas.';
        }

        $mensaje = 'Al eliminar esta carpeta:';
        
        if ($subcarpetas > 0) {
            $mensaje .= "\n- Se eliminarán {$subcarpetas} subcarpeta(s)";
        }
        
        if ($bibliotecas > 0) {
            $mensaje .= "\n- Se moverán {$bibliotecas} elemento(s) de biblioteca a la carpeta padre";
        }

        return $mensaje;
    }

    // Obtener bibliotecas de esta carpeta y todas las subcarpetas
    public function bibliotecasRecursivas()
    {
        $bibliotecas = $this->bibliotecas();
        
        foreach ($this->subcarpetasRecursivas as $subcarpeta) {
            $bibliotecas = $bibliotecas->union($subcarpeta->bibliotecas());
        }
        
        return $bibliotecas;
    }

    // Obtener todas las subcarpetas recursivamente (para eliminación)
    public function todasLasSubcarpetas()
    {
        $subcarpetas = collect();
        
        foreach ($this->subcarpetas as $subcarpeta) {
            $subcarpetas->push($subcarpeta);
            $subcarpetas = $subcarpetas->merge($subcarpeta->todasLasSubcarpetas());
        }
        
        return $subcarpetas;
    }

    // Contar elementos de biblioteca recursivamente
    public function contarBibliotecasRecursivas()
    {
        $count = $this->bibliotecas()->whereNull('deleted_at')->count();
        
        foreach ($this->subcarpetas as $subcarpeta) {
            $count += $subcarpeta->contarBibliotecasRecursivas();
        }
        
        return $count;
    }

    /**
     * Contar elementos totales (subcarpetas + bibliotecas) recursivamente
     */
    public function contarElementosRecursivos()
    {
        $total = 0;
        
        // Contar bibliotecas en esta carpeta
        $total += $this->bibliotecas()->whereNull('deleted_at')->count();
        
        // Contar subcarpetas y sus elementos recursivamente
        foreach ($this->subcarpetas as $subcarpeta) {
            $total += 1; // La subcarpeta misma
            $total += $subcarpeta->contarElementosRecursivos();
        }
        
        return $total;
    }
}