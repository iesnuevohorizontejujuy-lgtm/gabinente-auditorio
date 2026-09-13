<?php

namespace App\Models;

use Database\Factories\CarreraFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nombre
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, User> $profesores
 */
#[Fillable(['nombre'])]
class Carrera extends Model
{
    /** @use HasFactory<CarreraFactory> */
    use HasFactory;

    /** @return BelongsToMany<User, $this> */
    public function profesores(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
