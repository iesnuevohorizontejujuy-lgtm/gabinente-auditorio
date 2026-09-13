<?php

namespace App\Models;

use Database\Factories\FeriadoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $fecha
 * @property string $nombre
 * @property string|null $tipo
 * @property string $jurisdiccion
 * @property string $fuente
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['fecha', 'nombre', 'tipo', 'jurisdiccion', 'fuente'])]
class Feriado extends Model
{
    /** @use HasFactory<FeriadoFactory> */
    use HasFactory;

    /**
     * @param  Builder<Feriado>  $query
     * @return Builder<Feriado>
     */
    public function scopeEntre(Builder $query, Carbon $inicio, Carbon $fin): Builder
    {
        return $query
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<', $fin->toDateString());
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }
}
