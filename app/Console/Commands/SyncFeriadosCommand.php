<?php

namespace App\Console\Commands;

use App\Actions\Feriados\SyncFeriadosNacionales;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('feriados:sincronizar {anio? : Año calendario que se desea sincronizar}')]
#[Description('Sincroniza y persiste los feriados nacionales desde ArgentinaDatos')]
class SyncFeriadosCommand extends Command
{
    public function handle(SyncFeriadosNacionales $action): int
    {
        $anio = (int) ($this->argument('anio') ?: now()->year);

        if ($anio < 2016 || $anio > now()->year) {
            $this->error('El año debe estar comprendido entre 2016 y el año actual.');

            return self::INVALID;
        }

        try {
            $cantidad = $action->handle($anio);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('No fue posible sincronizar los feriados.');

            return self::FAILURE;
        }

        $this->info("Se sincronizaron {$cantidad} feriados nacionales de {$anio}.");

        return self::SUCCESS;
    }
}
