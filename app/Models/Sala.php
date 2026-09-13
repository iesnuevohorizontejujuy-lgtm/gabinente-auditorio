<?php

namespace App\Models;

use App\Enums\SalaDisponibilidad;
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
 * @property string|null $img
 * @property string|null $ubicacion
 * @property int $capacidad
 * @property string $hora_inicio_operativo
 * @property string $hora_fin_operativo
 * @property SalaDisponibilidad $disponibilidad
 * @property bool $activa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $reservas_count
 * @property-read Collection<int, SalaImagen> $imagenes
 * @property-read Collection<int, Reserva> $reservas
 */
#[Fillable(['nombre', 'descripcion', 'img', 'ubicacion', 'capacidad', 'hora_inicio_operativo', 'hora_fin_operativo', 'disponibilidad', 'activa'])]
class Sala extends Model
{
    /** @use HasFactory<SalaFactory> */
    use HasFactory;

    /** @return HasMany<Reserva, $this> */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    /** @return HasMany<SalaImagen, $this> */
    public function imagenes(): HasMany
    {
        return $this->hasMany(SalaImagen::class)->orderBy('orden');
    }

    /**
     * @param  Builder<Sala>  $query
     * @return Builder<Sala>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    public function disponibilidadActual(): SalaDisponibilidad
    {
        if (! $this->activa || $this->disponibilidad === SalaDisponibilidad::FueraDeServicio) {
            return SalaDisponibilidad::FueraDeServicio;
        }

        if (! $this->estaAbiertaAhora()) {
            return SalaDisponibilidad::Cerrada;
        }

        return $this->disponibilidad;
    }

    public function horarioOperativo(): string
    {
        return substr($this->hora_inicio_operativo, 0, 5).' a '.substr($this->hora_fin_operativo, 0, 5);
    }

    private function estaAbiertaAhora(): bool
    {
        $horaActual = now()->format('H:i:s');

        return $horaActual >= $this->hora_inicio_operativo
            && $horaActual < $this->hora_fin_operativo;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'capacidad' => 'integer',
            'disponibilidad' => SalaDisponibilidad::class,
        ];
    }
}
