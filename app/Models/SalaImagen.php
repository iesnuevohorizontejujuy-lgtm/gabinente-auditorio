<?php

namespace App\Models;

use Database\Factories\SalaImagenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sala_id
 * @property string $ruta
 * @property int $orden
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Sala $sala
 */
#[Fillable(['ruta', 'orden'])]
class SalaImagen extends Model
{
    /** @use HasFactory<SalaImagenFactory> */
    use HasFactory;

    protected $table = 'sala_imagenes';

    /** @return BelongsTo<Sala, $this> */
    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
        ];
    }
}
