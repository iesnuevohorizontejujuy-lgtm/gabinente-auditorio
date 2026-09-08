<?php

namespace App\Models;

use App\Enums\ReservaEstado;
use Database\Factories\ReservaHistorialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reserva_id
 * @property int|null $actor_id
 * @property ReservaEstado|null $estado_anterior
 * @property ReservaEstado $estado_nuevo
 * @property string|null $motivo
 * @property Carbon|null $created_at
 * @property-read Reserva $reserva
 * @property-read User|null $actor
 */
#[Fillable(['reserva_id', 'actor_id', 'estado_anterior', 'estado_nuevo', 'motivo'])]
class ReservaHistorial extends Model
{
    /** @use HasFactory<ReservaHistorialFactory> */
    use HasFactory;

    /** @return BelongsTo<Reserva, $this> */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estado_anterior' => ReservaEstado::class,
            'estado_nuevo' => ReservaEstado::class,
        ];
    }
}
