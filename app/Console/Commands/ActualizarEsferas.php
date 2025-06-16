<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ActualizarEsferas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:actualizar-esferas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'envia datos de esferas y llenaderas a aws mediante api externa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando actualización de esferas...');

        $data = DB::connection('mysql')->table('esferas_web')->get()->toArray();

        $datosFormateados = array_map(function ($item) {
            return [
                'density'            => $item->densidad,
                'density20'          => $item->densidadCorregida,
                'temperature'        => $item->temperaturaEsfera,
                'lower_pressure'     => $item->presionFondo,
                'dome_pressure'      => $item->presionDomo,
                'mass'               => $item->masa,
                'volume'             => $item->volumen,
                'barrel_volume'      => $item->volumenBarril,
                'corrected_volume'   => $item->volumenCorregido,
                'corr_barrel_volume' => $item->volumenCorrBarril,
                'level'              => $item->nivel,
                'percent_level'      => $item->nivelPorcentaje,
                'id'                 => $item->id == 'TE301A' ? 'TE-101A' : 'TE-101B',
            ];
        }, $data);

        $response = Http::put('https://api-mon.ducter-management.com/api/v1/sphere-realtime/update-all', [
            'data' => $datosFormateados
        ]);

        if ($response->successful()) {
            $this->info('Esferas actualizadas correctamente.');
        } else {
            $this->error('Error al actualizar esferas: ' . $response->body());
        }

        return Command::SUCCESS;
    }
}
