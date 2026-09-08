<?php

namespace App\Models;

use Database\Factories\SalaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $ubicacion
 * @property int $capacidad
 * @property bool $activa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $reservas_count
 * @property-read Collection<int, Reserva> $reservas
 */
#[Fillable(['nombre', 'descripcion', 'ubicacion', 'capacidad', 'activa'])]
class Sala extends Model
{
    /** @use HasFactory<SalaFactory> */
    use HasFactory;

    /** @return HasMany<Reserva, $this> */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    /**
     * @param  Builder<Sala>  $query
     * @return Builder<Sala>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'capacidad' => 'integer',
        ];
    }
}
