<?php

namespace App\Models;

use App\Enums\ReservaEstado;
use Carbon\CarbonInterface;
use Database\Factories\ReservaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sala_id
 * @property int $profesor_id
 * @property string $titulo
 * @property string|null $descripcion
 * @property int $cantidad_asistentes
 * @property CarbonInterface $inicio
 * @property CarbonInterface $fin
 * @property ReservaEstado $estado
 * @property string|null $motivo_resolucion
 * @property int|null $resuelta_por_id
 * @property CarbonInterface|null $resuelta_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Sala $sala
 * @property-read User $profesor
 * @property-read User|null $resueltaPor
 * @property-read Collection<int, ReservaHistorial> $historial
 */
#[Fillable([
    'sala_id',
    'profesor_id',
    'titulo',
    'descripcion',
    'cantidad_asistentes',
    'inicio',
    'fin',
    'estado',
    'motivo_resolucion',
    'resuelta_por_id',
    'resuelta_at',
])]
class Reserva extends Model
{
    /** @use HasFactory<ReservaFactory> */
    use HasFactory;

    /** @return BelongsTo<Sala, $this> */
    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class);
    }

    /** @return BelongsTo<User, $this> */
    public function profesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resueltaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelta_por_id');
    }

    /** @return HasMany<ReservaHistorial, $this> */
    public function historial(): HasMany
    {
        return $this->hasMany(ReservaHistorial::class)->oldest();
    }

    /**
     * @param  Builder<Reserva>  $query
     * @return Builder<Reserva>
     */
    public function scopeAprobadas(Builder $query): Builder
    {
        return $query->where('estado', ReservaEstado::Aprobada);
    }

    /**
     * Limit reservations to those intersecting a half-open time range.
     *
     * @param  Builder<Reserva>  $query
     * @return Builder<Reserva>
     */
    public function scopeEntre(Builder $query, CarbonInterface $inicio, CarbonInterface $fin): Builder
    {
        return $query
            ->where('inicio', '<', $fin)
            ->where('fin', '>', $inicio);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estado' => ReservaEstado::class,
            'cantidad_asistentes' => 'integer',
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'resuelta_at' => 'datetime',
        ];
    }
}
