<?php

namespace App\Console\Commands;

use App\Http\Controllers\CargasController;
use App\Mail\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ExecuteFunctionsCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:execute-functions-c-s-v';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecutar funciones de lunes a viernes a las 6 am';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');
        Log::info("Comando iniciado: $date");

        $allFilenames = [];

        // Array asociativo con las funciones y sus rutas nombradas.
        // ¡Recuerda definir estas rutas en routes/web.php o api.php!
        $functions = [
            'getInventarioEsferas' => 'getInventarioEsferas',
            'getTotalInvEsferas' => 'getTotalInvEsferas',
            'getSellos' => 'getSellos',
            'getCompanias' => 'getCompanias',
            'getCargasDiarias' => 'getCargasDiarias',
            'getRDC' => 'getRDC',
        ];

        try {
            foreach ($functions as $functionName => $routeName) {
                Log::info("Iniciando función: $functionName");

                $response = Http::withOptions([
                    'verify' => false, // Desactiva la verificación del certificado del par
                ])->get(route($routeName));

                if ($response->successful()) {
                    $data = $response->json();

                    Log::info("Mensaje de $functionName: " . $data['message'] ?? 'Sin mensaje'); // Manejo de valores nulos
                    Log::info("Status de $functionName: " . $data['status'] ?? 'Sin status');
                    Log::info("Archivos de $functionName: " . print_r($data['files'] ?? [], true)); // Manejo de valores nulos y array vacío

                    if (isset($data['files']) && is_array($data['files'])) {
                        $allFilenames = array_merge($allFilenames, $data['files']);
                    } else {
                        Log::warning("La respuesta de $functionName no contiene un array de 'files' válido o está vacío.");
                    }

                    Log::info("$functionName ejecutada con éxito.");
                } else {
                    Log::error("Error al obtener datos de $functionName: " . $response->status() . ' - ' . $response->body());
                    $this->error("Error al ejecutar $functionName. Revisa los logs.");
                    return Command::FAILURE; // Sale del comando si falla una función
                }
            }

            // Después de ejecutar todas las funciones:
            Log::info("Total de archivos generados: " . count($allFilenames));
            Log::info("Archivos generados: " . print_r($allFilenames, true));

            if (!empty($allFilenames)) { // Verifica si hay archivos para enviar por correo
                Mail::to(env('MAIL_RECEIVER_ADDRESS'))
                    ->cc(env('MAIL_CC_ADDRESS'))
                    ->send(new Notification($allFilenames));
                Log::info("Correo enviado con " . count($allFilenames) . " archivos adjuntos.");

            } else {
                Log::warning("No se generaron archivos para enviar por correo.");
            }

        } catch (\Exception $e) {
            Log::error('Error general: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            $this->error('Error al ejecutar el comando. Revisa los logs.');
            return Command::FAILURE;
        }

        Log::info("Comando finalizado: $date");
        return Command::SUCCESS;
    }
}
