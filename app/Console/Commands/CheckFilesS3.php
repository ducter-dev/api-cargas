<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckFilesS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-files-s3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisar archivos faltantes y ejecutar funciones si es necesario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now('America/Mexico_City');

        // Ajustar fecha según la hora actual
        if ($now->hour >= 6) {
            $adjustedDate = $now->subDay();
        } else {
            $adjustedDate = $now->subDays(2);
        }

        $dateStr = $adjustedDate->format('Y-m-d');

        $expectedFiles = $this->generateNameFiles($dateStr);

        // Verificar archivos faltantes en la API
        try {
            $response = Http::withHeaders([
                'app_key' => env('APP_KEY'),
            ])->get(env('API_URL').'/'.$dateStr);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['missing_files'] ?? [])) {
                    $missingFiles = $data['missing_files'];

                    $intersection = array_intersect($expectedFiles, $missingFiles);

                    if (!empty($intersection)) {
                        Log::info("Archivos que faltan: " . implode(", ", $intersection));
                        $this->call('app:execute-functions-c-s-v');
                    }
                }
            } else {
                Log::error("Error al verificar archivos: " . $response->status() . ' - ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Error general al verificar archivos: " . $e->getMessage());
            Log::error($e->getTraceAsString());
        }


    }

    /**
     * Genera los nombres esperados de archivos para una fecha dada.
     *
     * @param string $date Fecha en formato Y-m-d.
     * @return array Lista de nombres de archivos esperados.
     */
    private function generateNameFiles(string $date): array
    {
        // Separar la fecha
        [$fullyear, $month, $day] = explode('-', $date);
        $year = substr($fullyear, 2);

        $spheresIrge = array_map(
            fn($i) => "inventario_esfera_TE-301" . chr(65 + $i) . "_{$fullyear}{$month}{$day}.csv",
            range(0, 1)
        );

        $otherFiles = [
            "reporte_cargas_diarias_{$fullyear}-{$month}-{$day}.csv",
            "reporte_companias_{$fullyear}-{$month}-{$day}.csv",
            "reporte_sellos_{$fullyear}-{$month}-{$day}.csv",
            "reporte_rdc_{$fullyear}-{$month}-{$day}.csv",
            "total_inventario_esferas_{$fullyear}{$month}{$day}.csv",
        ];

        return array_merge($spheresIrge, $otherFiles);
    }
}
