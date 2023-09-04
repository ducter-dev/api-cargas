<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CargasController extends Controller
{
    public function getCargasIRGE(Request $request)
    {
        try {
            $fecha = $request->fecha;
            $fechaBD = Carbon::parse($fecha)->format('Ymd');

            #   Obtener las cargas de SCADA
            $cargasSCA = DB::table('TankTrucks')
                ->where('DiaReporte05', $fechaBD)
                ->get();

            #   Obtener las cargas documentadas del día.
            $documentados = DB::connection('mysql')
                ->table('embarques')
                ->where('fechaRep_llenado', $fecha)
                ->get();

            //dd($documentados);
            #   Comparar las cargas en documentación contra las reales
            $faltantes = [];
            foreach ($cargasSCA as $cargaSCA) {
                $pg = trim("PG-" . substr($cargaSCA->TankTruck, 2));
                $folio = intval($cargaSCA->FolioPLC);
                
                $exist = false;
                foreach ($documentados as $documentado) {
                    $doc_pg = $documentado->id_pg;
                    $doc_folio =  $documentado->folioCarga;
                    if ($pg === $doc_pg && $folio == $doc_folio) {
                        $exist = true;
                        break 1;
                    }
                }
                if (!$exist) {
                    array_push($faltantes, $cargaSCA);
                }
            }
            //dd($faltantes);
            $insertados = 0;
            foreach ($faltantes as $faltante) {

                $tipoTanque = '';
                switch ($faltante->TankTruckTypeID) {
                    case 0:
                        $tipoTanque = '';
                        break;
                    case 1:
                        $tipoTanque = 'A';
                        break;
                    case 2:
                        $tipoTanque = 'B';
                        break;
                    default:
                        # code...
                        break;
                }
                $embfolio = $faltante->FolioPLC;
                $numeroLlenadera = "L-" . $faltante->LoadingBayNumber;
                $claveLlenado = $fechaBD . $numeroLlenadera . $embfolio . $tipoTanque;
                $pg = trim("PG-" . substr($faltante->TankTruck, 2));
                $entradaLlenado = "$faltante->EntryYear-$faltante->EntryMonth-$faltante->EntryDay $faltante->LoadIniTime:00";
                $embarque = 0;
                $inicioCarga = "$faltante->EntryYear-$faltante->EntryMonth-$faltante->EntryDay $faltante->LoadIniTime:00";
                $finCarga = "$faltante->EntryYear-$faltante->EntryMonth-$faltante->EntryDay $faltante->LoadEndTime:00";
                $fechaJornada = $fecha;
                $contenidoLlenado = $faltante->LoadVolNat_Lts;
                $densidad = floatval("0.$faltante->LoadDensityNat");
                $densidad20 = floatval("0.$faltante->LoadDensityCor");
                
                $temperatura = $faltante->LoadTemp;
                $presion = $faltante->LoadPres;
                $masa = $faltante->LoadMass_Tons;
                $masaKgs = floatval("$faltante->LoadMass_kgs}.000");
                $masaPura = $faltante->LoadMass_kgs;
                $volumen = $faltante->LoadVolNat_Bls;
                $volumen20 = $faltante->LoadVolCor_Bls;
                $volumenPuro = $faltante->LoadVolNat_Bls;
                $volumen20Puro = $faltante->LoadVolCor_Bls;
                
                $porcentajeLlenado = $faltante->LoadPercent;
                $capacidad = $faltante->Capacity;
                $restante = $faltante->StandardCapacity;
                $modo = 2;
                $captura = 1;

                $insertado = DB::connection('mysql')
                ->table('embarques')
                ->insert([
                    'clave_llenado' => $claveLlenado,
                    'id_pg' => $pg,
                    'entrada_llenado' => $entradaLlenado,
                    'folioCarga' => $embfolio,
                    'embarque' => $embarque,
                    'inicioCarga_llenado' => $inicioCarga,
                    'finCarga_llenado' => $finCarga,
                    'fechaRep_llenado' => $fechaJornada,
                    'contenido_llenado' => $contenidoLlenado,
                    'densidad_llenado' => $densidad,
                    'densidad20_llenado' => $densidad20,
                    'temperatura_llenado' => $temperatura,
                    'presion_llenado' => $presion,
                    'masa_llenado' => $masa,
                    'masaKgs_llenado' => $masaKgs,
                    'masaPura_llenado' => $masaPura,
                    'volumen_llenado' => $volumen,
                    'volumen20_llenado' => $volumen20,
                    'volumenPuro' => $volumenPuro,
                    'volumen20Puro' => $volumen20Puro,
                    'llenadera_llenado' => $numeroLlenadera,
                    'porcentaje_llenado' => $porcentajeLlenado,
                    'capacidad90_llenado' => $capacidad,
                    'restante' => $restante,
                    'modo' => $modo,
                    'capturado_llenado' => $captura,
                ]);

                if ($insertado) {
                    $insertados++;
                } 
            }

            return response()->json([
                'message' => "Se insertaron $insertados registros."
            ],201);


        } catch (\Throwable $th) {
            echo $th;
        }

    }
}
