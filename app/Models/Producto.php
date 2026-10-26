<?php

// app/Models/Producto.php

namespace App\Models;

use App\Enums\TipoProducto;
use App\Enums\UnidadComercial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    // protected $fillable = [
    //     'nombre',
    //     'descripcion',
    //     'precio',
    //     'imagen',
    //     'categoria_id',
    //     'personalizable',
    //     'unidades',
    //     'rolls_total',
    //     'rolls_envueltos',
    //     'rolls_fritos',
    //     // 'slug' si quieres asignarlo masivamente
    // ];

    protected $guarded = [];

    protected $attributes = ['tipo' => 'simple', 'activo' => true, 'orden' => 0, 'unidad_tipo' => 'pieza', 'descripcion' => ''];

    protected $casts = [
        'catalog_policy' => 'array',
        'personalizable' => 'boolean',
        'tipo' => TipoProducto::class,
        'activo' => 'boolean',
        'orden' => 'integer',
        'unidades' => 'integer',
        'unidad_tipo' => UnidadComercial::class,
    ];

    // Generar slug automáticamente
    public static function boot()
    {
        parent::boot();

        static::creating(function ($p) {
            $slug = Str::slug($p->nombre);
            $original = $slug;
            $i = 1;

            while (Producto::where('slug', $slug)->exists()) {
                $slug = $original.'-'.$i++;
            }

            $p->slug = $slug;
        });

        static::updating(function ($p) {
            // Si el nombre no ha cambiado, no regeneres el slug
            if (! $p->isDirty('nombre')) {
                return;
            }

            $slug = Str::slug($p->nombre);
            $original = $slug;
            $i = 1;

            while (Producto::where('slug', $slug)->where('id', '!=', $p->id)->exists()) {
                $slug = $original.'-'.$i++;
            }

            $p->slug = $slug;
        });
    }

    // Relación con Categoría
    public function scopeDisponible(Builder $query): Builder
    {
        return $query->where('productos.activo', true)->whereHas('categoria', fn ($category) => $category->where('activo', true));
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function envolturas(): BelongsToMany
    {
        return $this->ingredientes()->where('ingredientes.tipo', 'envoltura');
    }

    // Relación muchos a muchos con Ingredientes (con cantidad_permitida)
    public function ingredientes(): BelongsToMany
    {
        return $this->belongsToMany(Ingrediente::class)
            ->using(IngredienteProducto::class)
            ->withPivot('cantidad_permitida', 'cantidad', 'unidad', 'obligatorio', 'reemplazable', 'orden', 'metadata');
    }

    // Relación “swappables” (opcionalmente podrías incluir pivot/timestamps)
    public function swappables(): BelongsToMany
    {
        return $this->belongsToMany(
            Ingrediente::class,
            'producto_swappable_ingredient'
        );
        // ->withPivot('cantidad_permitida')
        // ->withTimestamps()
    }

    public function gruposOpciones(): BelongsToMany
    {
        return $this->belongsToMany(GrupoOpcion::class, 'grupo_opcion_producto', 'producto_id', 'grupo_opcion_id')
            ->using(AsignacionGrupo::class)->withPivot(AsignacionGrupo::COLUMNS)->withTimestamps()->orderByPivot('orden');
    }

    public function bloques(): HasMany
    {
        return $this->hasMany(BloquePromocion::class)->orderBy('orden');
    }

    public function reglas(): HasMany
    {
        return $this->hasMany(ReglaPromocion::class);
    }

    public function promocion(): HasOne
    {
        return $this->hasOne(Promocion::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }
}
