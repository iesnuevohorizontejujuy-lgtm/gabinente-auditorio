<?php

namespace App\Actions\Feriados;

use App\Models\Feriado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SyncFeriadosNacionales
{
    public function handle(int $anio): int
    {
        $response = Http::baseUrl(config('services.argentina_datos.url'))
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(10)
            ->retry([200, 500])
            ->get("/v1/feriados/{$anio}")
            ->throw();

        $feriados = Validator::make(
            ['feriados' => $response->json()],
            [
                'feriados' => ['required', 'array'],
                'feriados.*.fecha' => ['required', 'date_format:Y-m-d'],
                'feriados.*.nombre' => ['required', 'string', 'max:255'],
                'feriados.*.tipo' => ['nullable', 'string', 'max:100'],
            ],
        )->validate()['feriados'];

        return DB::transaction(function () use ($feriados): int {
            foreach ($feriados as $feriado) {
                Feriado::query()->updateOrCreate(
                    [
                        'fecha' => $feriado['fecha'],
                        'jurisdiccion' => 'nacional',
                        'nombre' => $feriado['nombre'],
                    ],
                    [
                        'tipo' => $feriado['tipo'] ?? null,
                        'fuente' => 'ArgentinaDatos',
                    ],
                );
            }

            return count($feriados);
        });
    }
}
