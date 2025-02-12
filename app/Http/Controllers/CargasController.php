<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                ->orderBy('EntryID', 'desc')
                ->get();
            $cargas = [];
            foreach ($cargasSCA as $cargaSCA) {
                if ($cargaSCA->TipoCargas === "VALIDA" && $cargaSCA->EntryYear != NULL) {

                    $tipoTanque = '';
                    switch ($cargaSCA->TankTruckTypeID) {
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

                    $iniDate = explode('/', $cargaSCA->LoadIniDate);
                    $iniDate_year = $iniDate[2];
                    $iniDate_month = $iniDate[1];
                    $iniDate_day = $iniDate[0];

                    $endDate = explode('/', $cargaSCA->LoadEndDate);
                    $endDate_year = $endDate[2];
                    $endDate_month = $endDate[1];
                    $endDate_day = $endDate[0];



                    $embfolio = $cargaSCA->FolioPLC;
                    $numeroLlenadera = "L-" . $cargaSCA->LoadingBayNumber;
                    $claveLlenado = $fechaBD . $numeroLlenadera . "-". $embfolio . $tipoTanque;
                    $pg = trim("PG-" . substr($cargaSCA->TankTruck, 2));
                    $entradaLlenado = "$cargaSCA->EntryYear-$cargaSCA->EntryMonth-$cargaSCA->EntryDay $cargaSCA->EntryTime:00";
                    $embarque = 0;

                    $inicioCarga = "$iniDate_year-$iniDate_month-$iniDate_day $cargaSCA->LoadIniTime:00";
                    //dd($inicioCarga);
                    $finCarga = "$endDate_year-$endDate_month-$endDate_day $cargaSCA->LoadEndTime:00";
                    //dd($finCarga);

                    $fechaJornada = $fecha;
                    $contenidoLlenado = $cargaSCA->LoadVolNat_Lts;
                    $densidad = floatval("0$cargaSCA->LoadDensityNat");
                    $densidad20 = floatval("0$cargaSCA->LoadDensityCor");

                    $temperatura = $cargaSCA->LoadTemp;
                    $presion = $cargaSCA->LoadPres;
                    $masa = $cargaSCA->LoadMass_Tons;
                    $masaKgs = floatval("$cargaSCA->LoadMass_kgs}.000");
                    $masaPura = $cargaSCA->LoadMass_kgs;
                    $volumen = $cargaSCA->LoadVolNat_Bls;
                    $volumen20 = $cargaSCA->LoadVolCor_Bls;
                    $volumenPuro = $cargaSCA->LoadVolNat_Bls;
                    $volumen20Puro = $cargaSCA->LoadVolCor_Bls;

                    $porcentajeLlenado = $cargaSCA->LoadPercent;
                    $capacidad = $cargaSCA->Capacity;
                    $restante = $cargaSCA->StandardCapacity;
                    $modo = 2;
                    $captura = 1;

                    $objCarga = ([
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

                    array_push($cargas, $objCarga);
                }
            }

            return response()->json([
                'message' => "Datos leídos correctamente.",
                'data' => $cargas
            ],200);


        } catch (\Throwable $th) {
            echo $th;
        }

    }

    public function getFaltantesIRGE(Request $request)
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
                    if ($cargaSCA->TipoCargas === "VALIDA" && $cargaSCA->EntryYear != NULL) {
                        array_push($faltantes, $cargaSCA);
                    }
                }
            }
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

                $iniDate = explode('/', $cargaSCA->LoadIniDate);
                $iniDate_year = $iniDate[2];
                $iniDate_month = $iniDate[1];
                $iniDate_day = $iniDate[0];

                $endDate = explode('/', $cargaSCA->LoadEndDate);
                $endDate_year = $endDate[2];
                $endDate_month = $endDate[1];
                $endDate_day = $endDate[0];



                $embfolio = $faltante->FolioPLC;
                $numeroLlenadera = "L-" . $faltante->LoadingBayNumber;
                $claveLlenado = $fechaBD . $numeroLlenadera . "-" . $embfolio . $tipoTanque;
                $pg = trim("PG-" . substr($faltante->TankTruck, 2));
                $entradaLlenado = "$faltante->EntryYear-$faltante->EntryMonth-$faltante->EntryDay $faltante->EntryTime:00";
                $embarque = 0;

                $inicioCarga = "$iniDate_year-$iniDate_month-$iniDate_day $faltante->LoadIniTime:00";
                //dd($inicioCarga);
                $finCarga = "$endDate_year-$endDate_month-$endDate_day $faltante->LoadEndTime:00";
                //dd($finCarga);

                $fechaJornada = $fecha;
                $contenidoLlenado = $faltante->LoadVolNat_Lts;
                $densidad = floatval("0$faltante->LoadDensityNat");
                $densidad20 = floatval("0$faltante->LoadDensityCor");

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
                $insertado = true;
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

    public function getCargasTPA(Request $request)
    {
        try {
            $fecha = Carbon::parse($request->fecha);
            $fechaIni = $fecha->format('Y-m-d') . ' 05:00:00';
            $fechaFin = $fecha->addDay(1)->format('Y-m-d') . ' 05:00:00';

            #   Obtener las cargas de SCADA
            $cargasSCA = DB::table('Cargas')
                ->where('HoraFin', '>=', $fechaIni)
                ->where('HoraFin', '<=', $fechaFin)
                ->orderBy('HoraFin', 'desc')
                ->get();
            $cargas = [];
            foreach ($cargasSCA as $cargaSCA) {
                if (floatval($cargaSCA->Masa) > 0) {

                    $llenadera = $cargaSCA->Llenadera;
                    $pg = $cargaSCA->AT;
                    $horaEntrada = $cargaSCA->HoraEntrada;
                    $horaInicio = $cargaSCA->HoraInicio;
                    $horaFin = $cargaSCA->HoraFin;
                    $fechaJornada = $request->fecha;

                    $temp = round(floatval($cargaSCA->Temp),2);
                    $presion = round(floatval($cargaSCA->Presion),3);
                    $densidad = floatval($cargaSCA->Densidad);
                    $dens20 = floatval($cargaSCA->Dens20);
                    $volumen = round(floatval($cargaSCA->Volumen),3);
                    $vol20 = round(floatval($cargaSCA->Vol20),3);
                    $porcentaje = round(floatval($cargaSCA->Porcentaje),2);
                    $masa = floatval($cargaSCA->Masa);
                    $color = $cargaSCA->Color;
                    $externalKey = $cargaSCA->ExternalKey;
                    $notes = $cargaSCA->Notes;

                    //$fechaClave = Carbon::parse($horaFin)->format('Ymd');
                    //$claveLlenado = $fechaClave . $llenadera . $embfolio . $tipoTanque;

                    $objCarga = ([
                        'id_pg' => $pg,
                        'entrada_llenado' => $horaEntrada,
                        'inicioCarga_llenado' => $horaInicio,
                        'finCarga_llenado' => $horaFin,
                        'fechaRep_llenado' => $fechaJornada,
                        'densidad_llenado' => $densidad,
                        'densidad20_llenado' => $dens20,
                        'temperatura_llenado' => $temp,
                        'presion_llenado' => $presion,
                        'masa_llenado' => $masa,
                        'masaKgs_llenado' => $masa * 1000,
                        'masaPura_llenado' => $masa,
                        'volumen_llenado' => $volumen,
                        'volumen20_llenado' => $vol20,
                        'volumenPuro' => $volumen,
                        'volumen20Puro' => $vol20,
                        'llenadera_llenado' => $llenadera,
                        'porcentaje_llenado' => $porcentaje,
                        'capturado_llenado' => 0,
                        'color' => $color,
                        'externalKey' => $externalKey,
                        'notes' => $notes
                    ]);

                    array_push($cargas, $objCarga);
                }
            }

            return response()->json([
                'message' => "Datos leídos correctamente.",
                'data' => $cargas
            ],200);


        } catch (\Throwable $th) {
            echo $th;
        }

    }

    public function getFaltantesTPA(Request $request)
    {
        try {
            $fecha = Carbon::parse($request->fecha);
            $fechaIni = $fecha->format('Y-m-d') . ' 05:00:00';
            $fechaFin = $fecha->addDay(1)->format('Y-m-d') . ' 05:00:00';
            #   Obtener las cargas de SCADA
            $cargasSCA = DB::table('Cargas')
                ->where('HoraFin', '>=', $fechaIni)
                ->where('HoraFin', '<=', $fechaFin)
                ->orderBy('HoraFin', 'asc')
                ->get();
            $cargas = [];
            //dd($cargasSCA);

            #   Obtener las cargas documentadas del día.
            $documentados = DB::connection('mysql')
                ->table('embarques')
                ->where('fechaRep_llenado', $request->fecha)
                ->get();

            //dd($documentados);

            #   Comparar las cargas en documentación contra las reales
            $faltantes = [];
            foreach ($cargasSCA as $cargaSCA) {
                if (floatval($cargaSCA->Masa) > 0)
                {
                    $pg = $cargaSCA->AT;
                    //$folio = intval($cargaSCA->FolioPLC);     #   No tenemos folio en BD

                    $exist = false;
                    foreach ($documentados as $documentado) {
                        $doc_pg = $documentado->id_pg;
                        //$doc_folio = $documentado->folioCarga;  # No tenemos folio en BD
                        $llenadera = $cargaSCA->Llenadera;
                        $masa= floatval($cargaSCA->Masa);
                        $doc_masa = $documentado->masa_llenado;

                        $doc_llenadera = $documentado->llenadera_llenado;
                        if ($pg === $doc_pg && $llenadera == $doc_llenadera && $masa == $doc_masa) {
                            $exist = true;
                            break 1;
                        }
                    }
                    if (!$exist) {
                        if ($cargaSCA->Masa > 0) {
                            array_push($faltantes, $cargaSCA);
                        }
                    }
                }
            }

            $insertados = 0;
            foreach ($faltantes as $faltante) {



                $llenadera = $faltante->Llenadera;
                $pg = $faltante->AT;
                $horaEntrada = $faltante->HoraEntrada;
                $horaInicio = $faltante->HoraInicio;
                $horaFin = $faltante->HoraFin;
                $fechaJornada = $request->fecha;

                $temp = round(floatval($faltante->Temp),2);
                $presion = round(floatval($faltante->Presion),3);
                $densidad = floatval($faltante->Densidad);
                $dens20 = floatval($faltante->Dens20);
                $volumen = round(floatval($faltante->Volumen),3);
                $vol20 = round(floatval($faltante->Vol20),3);
                $porcentaje = round(floatval($faltante->Porcentaje),2);
                $masa = floatval($faltante->Masa);
                $color = $faltante->Color;
                $externalKey = $faltante->ExternalKey;
                $notes = $faltante->Notes;

                $fechaClave = Carbon::parse($horaFin)->format('Ymd');

                $dataLlenadera = $this->getFolioLlenadera($llenadera);
                $embfolio = $dataLlenadera[0]->folio_llenado + 1;
                $sufijoLlenadera = substr($llenadera, 5,2);
                $claveLlenado = $fechaClave . $sufijoLlenadera . $embfolio;
                $embarque = 0;
                $contenidoLlenado = 0;
                $capacidad = 0;
                $captura = 0;

                $insertado = true;
                $insertado = DB::connection('mysql')
                ->table('embarques')
                ->insert([
                    'clave_llenado' => $claveLlenado,
                    'id_pg' => $pg,
                    'entrada_llenado' => $horaEntrada,
                    'folioCarga' => $embfolio,
                    'embarque' => $embarque,
                    'inicioCarga_llenado' => $horaInicio,
                    'finCarga_llenado' => $horaFin,
                    'fechaRep_llenado' => $fechaJornada,
                    'contenido_llenado' => $contenidoLlenado,
                    'densidad_llenado' => $densidad,
                    'densidad20_llenado' => $dens20,
                    'temperatura_llenado' => $temp,
                    'presion_llenado' => $presion,
                    'masa_llenado' => $masa,
                    'masaKgs_llenado' => $masa * 1000,
                    'masaPura_llenado' => $masa,
                    'volumen_llenado' => $volumen,
                    'volumen20_llenado' => $vol20,
                    'volumenPuro' => $volumen,
                    'volumen20Puro' => $vol20,
                    'llenadera_llenado' => $llenadera,
                    'porcentaje_llenado' => $porcentaje,
                    'capacidad90_llenado' => $capacidad,
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


    public function getFolioLlenadera(string $llenadera)
    {
        $folio = DB::connection('mysql')
            ->table('control_llenado')
            ->select('folio_llenado')
            ->where('id_llenadera', $llenadera)
            ->orderBy('id', 'desc')
            ->limit(1)
            ->get();
        return $folio;
    }

    public function getCargasIRGE_Periodo(Request $request)
    {
        try {
            $fechaIni = $request->fechaIni;
            $fechaFin = $request->fechaFin;
            $fechaBDIni = Carbon::parse($fechaIni)->format('Ymd');
            $fechaBDFin = Carbon::parse($fechaFin)->format('Ymd');
            //dd($fechaBDIni);
            //dd($fechaBDFin);

            #   Obtener las cargas de SCADA
            $cargasSCA = DB::table('TankTrucks')
                ->whereBetween('DiaReporte05', [$fechaBDIni, $fechaBDFin])
                ->orderBy('EntryID', 'desc')
                ->get();
            $cargas = [];
            foreach ($cargasSCA as $cargaSCA) {
                if ($cargaSCA->TipoCargas === "VALIDA" && $cargaSCA->EntryYear != NULL) {

                    $tipoTanque = '';
                    switch ($cargaSCA->TankTruckTypeID) {
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

                    $iniDate = explode('/', $cargaSCA->LoadIniDate);
                    $iniDate_year = $iniDate[2];
                    $iniDate_month = $iniDate[1];
                    $iniDate_day = $iniDate[0];

                    $endDate = explode('/', $cargaSCA->LoadEndDate);
                    $endDate_year = $endDate[2];
                    $endDate_month = $endDate[1];
                    $endDate_day = $endDate[0];



                    $embfolio = intval($cargaSCA->FolioPLC);
                    $numeroLlenadera = "L-" . $cargaSCA->LoadingBayNumber;
                    //$claveLlenado = $fechaBD . $numeroLlenadera . "-" . $embfolio . $tipoTanque;
                    $pg = trim("PG-" . substr($cargaSCA->TankTruck, 2));
                    $entradaLlenado = "$cargaSCA->EntryYear-$cargaSCA->EntryMonth-$cargaSCA->EntryDay $cargaSCA->EntryTime:00";
                    $embarque = 0;

                    $inicioCarga = "$iniDate_year-$iniDate_month-$iniDate_day $cargaSCA->LoadIniTime:00";
                    //dd($inicioCarga);
                    $finCarga = "$endDate_year-$endDate_month-$endDate_day $cargaSCA->LoadEndTime:00";
                    //dd($finCarga);

                    //$fechaJornada = $fecha;
                    $contenidoLlenado = intval($cargaSCA->LoadVolNat_Lts);
                    $densidad = floatval("0$cargaSCA->LoadDensityNat");
                    $densidad20 = floatval("0$cargaSCA->LoadDensityCor");

                    $temperatura = floatval($cargaSCA->LoadTemp);
                    $presion = floatval($cargaSCA->LoadPres);
                    $masa = floatval($cargaSCA->LoadMass_Tons);
                    $masaKgs = floatval("$cargaSCA->LoadMass_kgs}.000");
                    $masaPura = intval($cargaSCA->LoadMass_kgs);
                    $volumen = floatval($cargaSCA->LoadVolNat_Bls);
                    $volumen20 = floatval($cargaSCA->LoadVolCor_Bls);
                    $volumenPuro = floatval($cargaSCA->LoadVolNat_Bls);
                    $volumen20Puro = floatval($cargaSCA->LoadVolCor_Bls);

                    $porcentajeLlenado = floatval($cargaSCA->LoadPercent);
                    $capacidad = intval($cargaSCA->Capacity);
                    $restante = intval($cargaSCA->StandardCapacity);
                    $modo = 2;
                    $captura = 1;

                    $objCarga = ([
                        //'clave_llenado' => $claveLlenado,
                        'id_pg' => $pg,
                        'entrada_llenado' => $entradaLlenado,
                        'folioCarga' => $embfolio,
                        'embarque' => $embarque,
                        'inicioCarga_llenado' => $inicioCarga,
                        'finCarga_llenado' => $finCarga,
                        //'fechaRep_llenado' => $fechaJornada,
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

                    array_push($cargas, $objCarga);
                }
            }

            $totalMasa = array_reduce($cargas, function ($carry, $item){
                $carry = $carry + $item['masa_llenado'];
                return $carry;
            });

            $totalVol = array_reduce($cargas, function ($carry, $item){
                $carry = $carry + $item['volumen20_llenado'];
                return $carry;
            });

            return response()->json([
                'message' => "Datos leídos correctamente.",
                'fecha_inicio' => $fechaIni,
                'fecha_fin' => $fechaFin,
                'total' => count($cargas),
                'masa' => ROUND($totalMasa,3),
                'volumen' => $totalVol,
            ],200);


        } catch (\Throwable $th) {
            echo $th;
        }

    }

    /**
     * Obtiene el inventario de la esfera por jornada
     */
    public function getInventarioEsferas(Request $request)
    {
        try {
            if ($request->fecha) {
                $fechaCarbon = Carbon::parse($request->fecha, 'America/Mexico_City');
                $strReportDay = $fechaCarbon->subDay()->format('Ymd');
                $carpeta = $fechaCarbon->format('Y-m-d') . "/irge/";
            } else {
                $strReportDay = Carbon::now('America/Mexico_City')->subDay()->format('Ymd');
                $carpeta = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d') . "/irge/";
            }

            $esferas = [1, 2];
            $filenames = [];
            $csvCombined = fopen('php://temp', 'r+');
            $headersAdded = false;

            // Mapeo de nombres de columnas para diariote_i.csv
            $columnMappings = [
                'Fecha' => 'Fecha',
                'Tiempo' => 'Hora',
                'VOL_NAT' => 'Barriles_Nat',
                'VOL_COR' => 'Barriles_20',
                'MASA' => 'Ton',
                'DENSIDADNAT' => 'DI_310',
                'DENSIDADCOR' => 'DI_310_CORR',
                'TEMP' => 'TI_310',
                'PRESION' => 'PI_311',
                'NIVEL' => 'LI_310',
                'PORCENTAJE' => 'Porcentaje_llenado',
                'Esfera' => 'Esfera',
            ];

            foreach ($esferas as $tanqueEsferico) {
                $nombreEsfera = $tanqueEsferico == 1 ? "TE-301A" : "TE-301B";

                $datos = DB::table('ValEsferas')
                    ->select(
                        DB::raw("'$nombreEsfera' AS Esfera"),
                        'Fecha',
                        'Tiempo',
                        'BLSNat AS VOL_NAT',
                        'BLSCor AS VOL_COR',
                        'VolTon AS MASA',
                        'DensidadNat AS DENSIDADNAT',
                        'DensidadCor AS DENSIDADCOR',
                        'Temp AS TEMP',
                        'Presion AS PRESION',
                        'Nivel AS NIVEL',
                        'Porcentaje AS PORCENTAJE'
                    )
                    ->where('Esfera', $tanqueEsferico)
                    ->where('DiaReporte05', $strReportDay)
                    ->orderBy('EntryID', 'asc')
                    ->get()
                    ->map(function ($item) {
                        return (array) $item; // Convertir cada resultado a un array asociativo
                    })
                    ->toArray();

                $fileName = 'inventario_esfera_' . $nombreEsfera . '_' . $strReportDay . '.csv';
                $filenames[] = $fileName;

                $csvContent = fopen('php://temp', 'r+');

                // Escribir encabezados en los archivos individuales
                $headers = ['Esfera', 'Fecha', 'Tiempo', 'VOL_NAT', 'VOL_COR', 'MASA',
                            'DENSIDADNAT', 'DENSIDADCOR', 'TEMP', 'PRESION', 'NIVEL', 'PORCENTAJE'];
                fputcsv($csvContent, $headers);

                // Escribir encabezados para diariote_i.csv si aún no se han añadido
                if (!$headersAdded) {
                    fputcsv($csvCombined, array_values($columnMappings));
                    $headersAdded = true;
                }

                foreach ($datos as $row) {
                    fputcsv($csvContent, $row);

                    // Preparar datos para diariote_i.csv sin la columna "Esfera"
                    $filteredRow = [];
                    foreach (array_keys($columnMappings) as $key) {
                        $filteredRow[] = $row[$key] ?? ''; // Evita error de clave no definida
                    }
                    fputcsv($csvCombined, $filteredRow);
                }

                rewind($csvContent);
                $csvOutput = stream_get_contents($csvContent);
                fclose($csvContent);

                // Guardar en S3
                Storage::disk('s3')->put($carpeta . $fileName, $csvOutput);
            }

            // Guardar el archivo combinado diariote_i.csv
            rewind($csvCombined);
            $csvFinalOutput = stream_get_contents($csvCombined);
            fclose($csvCombined);

            $finalFileName = 'diarioTE_irge.csv';
            Storage::disk('s3')->put($carpeta . $finalFileName, $csvFinalOutput);
            $filenames[] = $finalFileName;

            return response()->json([
                "message" => 'Archivos generados correctamente.',
                "status" => 200,
                "files" => $filenames,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al generar el archivo CSV.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el ultimo registro de la jornada anterior, actual y muestra sus diferencias
     */
    public function getTotalInvEsferas(Request $request)
    {
        try {
            if($request->fecha){
                $fechaCarbon = Carbon::parse($request->fecha, 'America/Mexico_City'); // Asumiendo que $request->fecha es una cadena de fecha válida

                $fechaHoy = $fechaCarbon->subDay()->format('Ymd');
                $carpeta = $fechaCarbon->format('Y-m-d') . "/irge/";
                $fechaAyer = $fechaCarbon->subDays()->format('Ymd');
            } else {
                $fechaHoy = Carbon::now('America/Mexico_City')->subDay()->format('Ymd');
                $fechaAyer = Carbon::now('America/Mexico_City')->subDays(2)->format('Ymd');
                $carpeta = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d')."/irge/";
            }
            $esferas = [1, 2];
            $filenames = [];
            // $fechaHoy = "20240724";
            // $fechaAyer = "20240723";

            $content = [];
            $counter = 1;
            foreach ($esferas as $tanqueEsferico) {
                $nombreEsfera = $tanqueEsferico == 1 ? "TE-301A" : "TE-301B";

                $datosAyer = DB::table('ValEsferas')
                    ->select('BLSNat', 'BLSCor', 'VolTon')
                    ->where('DiaReporte05', $fechaAyer)
                    ->where('Esfera', $tanqueEsferico)
                    ->orderBy('EntryID', 'DESC')
                    ->first();

                $content[$counter]['esfera'] = $nombreEsfera;
                $content[$counter]['VolNatAyer'] = $datosAyer->BLSNat;
                $content[$counter]['VolCorAyer'] = $datosAyer->BLSCor;
                $content[$counter]['TonAyer'] = $datosAyer->VolTon;

                $datosHoy = DB::table('ValEsferas')
                    ->select('BLSNat', 'BLSCor', 'VolTon')
                    ->where('DiaReporte05', $fechaHoy)
                    ->where('Esfera', $tanqueEsferico)
                    ->orderBy('EntryID', 'DESC')
                    ->first();

                $content[$counter]['VolNatHoy'] = $datosHoy->BLSNat;
                $content[$counter]['VolCorHoy'] = $datosHoy->BLSCor;
                $content[$counter]['TonHoy'] = $datosHoy->VolTon;

                $content[$counter]['difNat'] = $datosHoy->BLSNat - $datosAyer->BLSNat;
                $content[$counter]['difCor'] = $datosHoy->BLSCor - $datosAyer->BLSCor;
                $content[$counter]['difTon'] = $datosHoy->VolTon - $datosAyer->VolTon;

                $counter++;
            }

            $fileName = 'total_inventario_esferas_' . $fechaHoy . '.csv';
            $filenames [] = $fileName;
            // Crear contenido del CSV en memoria
            $csvContent = fopen('php://temp', 'r+');

            // Encabezados del CSV
            $headers = [
                'Esfera',
                'ANT_Barriles Natural',
                'ANT_Barriles 20',
                'ANT_Tons',
                'ACT_Barriles Natural',
                'ACT_Barriles 20',
                'ACT_Tons',
                'DIF_Barriles Natural',
                'DIF_Barriles 20',
                'DIF_Tons',
            ];
            fputcsv($csvContent, $headers);

            // Agregar los datos al contenido del CSV
            foreach ($content as $row) {
                fputcsv($csvContent, (array) $row);
            }

            // Rebobinar el contenido del CSV y obtenerlo como string
            rewind($csvContent);
            $csvOutput = stream_get_contents($csvContent);
            fclose($csvContent);

            // Guardar el archivo CSV en el storage de Laravel
            Storage::disk('s3')->put($carpeta . $fileName, $csvOutput);

            return response()->json([
                "message" => 'Archivo generado correctamente.',
                "status" => 200,
                "files" => $filenames
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al generar el archivo CSV.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function getSellos(Request $request)
    {
        try {
            if($request->fecha){
                $fechaCarbon = Carbon::parse($request->fecha, 'America/Mexico_City'); // Asumiendo que $request->fecha es una cadena de fecha válida

                $fecha = $fechaCarbon->subDay()->format('Y-m-d');
                $carpeta = $fechaCarbon->format('Y-m-d') . "/irge/";
            } else {
                $fecha = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d');
                $carpeta = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d')."/irge/";
            }

            $filenames = [];

            $entradas = DB::connection('mysql')
                ->table('entrada')
                ->where('entrada.fechaJornada', $fecha)
                ->join('accesos', 'entrada.noEmbarque', '=', 'accesos.embarque')
                ->join('subgrupos', 'entrada.subgrupo', '=', 'subgrupos.id')
                ->join('companias', 'entrada.compania', '=', 'companias.id')
                ->select(
                    'entrada.pg',
                    'accesos.id as idAcceso',
                    DB::raw("DATE_FORMAT(entrada.fechaJornada, '%W %d de %M de %Y') as fechaJ"),
                    'entrada.subgrupo as idSubgrupo',
                    'subgrupos.nombre as subgrupo',
                    'entrada.compania as idCompania',
                    'companias.nombre as compania'
                )
                ->orderBy('entrada.id', 'asc')
                // ->take(1)
                ->get();

            $content = [];
            $counter = 0;
            foreach ($entradas as $entrada) {

                $sellos = DB::connection('mysql')
                    ->table('sellos')
                    ->where('id_accesos', $entrada->idAcceso)
                    ->get();

                $sellosLargos = $sellos->where('tipo', 1)->pluck('sello');
                $sellosCortos = $sellos->where('tipo', 0)->pluck('sello');

                $content[$counter]['pg'] = $entrada->pg;
                $content[$counter]['subgrupo'] = $entrada->subgrupo;
                $content[$counter]['compania'] = $entrada->compania;
                $content[$counter]['sellosLargos'] = $sellosLargos->implode(', ');
                $content[$counter]['sellosCortos'] = $sellosCortos->implode(', ');
                $content[$counter]['cantidadSellosLargos'] = count($sellosLargos);
                $content[$counter]['cantidadSellosCortos'] = count($sellosCortos);

                $counter++;
            }

            $fileName = 'reporte_sellos_' . $fecha . '.csv';
            $filenames [] = $fileName;
            // Crear contenido del CSV en memoria
            $csvContent = fopen('php://temp', 'r+');

            // Encabezados del CSV
            $headers = [
                'pg',
                'subgrupo',
                'compania',
                'sellosLargos',
                'sellosCortos',
                'cantidadSellosLargos',
                'cantidadSellosCortos',
            ];
            fputcsv($csvContent, $headers);

            // Agregar los datos al contenido del CSV
            foreach ($content as $row) {
                fputcsv($csvContent, (array) $row);
            }

            // Rebobinar el contenido del CSV y obtenerlo como string
            rewind($csvContent);
            $csvOutput = stream_get_contents($csvContent);
            fclose($csvContent);

            // Guardar el archivo CSV en el storage de Laravel
            Storage::disk('s3')->put($carpeta . $fileName, $csvOutput);

            return response()->json([
                "message" => 'Archivo generado correctamente.',
                "status" => 200,
                "files" => $filenames
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al generar el archivo CSV.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function getCompanias(Request $request)
    {
        try {
            if($request->fecha){
                $fechaCarbon = Carbon::parse($request->fecha, 'America/Mexico_City'); // Asumiendo que $request->fecha es una cadena de fecha válida

                $fecha = $fechaCarbon->subDay()->format('Y-m-d');
                $carpeta = $fechaCarbon->format('Y-m-d') . "/irge/";
            } else {
                $fecha = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d');
                $carpeta = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d')."/irge/";
            }

            $filenames = [];

            DB::connection('mysql')->statement("SET sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
            $entradas = DB::connection('mysql')
                ->table('entrada')
                ->join('embarques', 'entrada.NoEmbarque', '=', 'embarques.embarque')
                ->join('companias', 'entrada.compania', '=', 'companias.id')
                ->leftJoin('subgrupos', 'entrada.subgrupo', '=', 'subgrupos.id')
                ->select(
                    'entrada.pg',
                    'companias.nombre as companiaStr',
                    'subgrupos.nombre as subgrupoStr',
                    'entrada.noEmbarque',
                    'entrada.masa',
                    'embarques.densidad_llenado as densidad',
                    'embarques.densidad20_llenado as densidad20',
                    'embarques.volumen_llenado as volumen',
                    'embarques.volumen20_llenado as volumen20',
                    DB::raw("CONCAT(entrada.magnatel, '','%') AS magnatel"),
                    'entrada.presionTanque',
                    DB::raw("DATE_FORMAT(embarques.inicioCarga_llenado, '%H:%i') AS inicioCarga_llenado"),
                    DB::raw("DATE_FORMAT(entrada.fechaSalida, '%H:%i') AS fechaSalida"),
                )
                ->where('entrada.fechaJornada', $fecha)
                ->groupBy('entrada.noEmbarque')
                ->orderBy('entrada.noEmbarque', 'asc')
                ->get()
                ->toArray();

            $fileName = 'reporte_companias_' . $fecha . '.csv';
            $filenames [] = $fileName;

            // Crear contenido del CSV en memoria
            $csvContent = fopen('php://temp', 'r+');

            // Encabezados del CSV
            $headers = [
                'pg',
                'companiaStr',
                'subgrupoStr',
                'noEmbarque',
                'masa',
                'densidad',
                'densidad20',
                'volumen',
                'volumen20',
                'magnatel',
                'presionTanque',
                'inicioCarga_llenado',
                'fechaSalida',
            ];
            fputcsv($csvContent, $headers);

            // Agregar los datos al contenido del CSV
            foreach ($entradas as $row) {
                fputcsv($csvContent, (array) $row);
            }

            // Rebobinar el contenido del CSV y obtenerlo como string
            rewind($csvContent);
            $csvOutput = stream_get_contents($csvContent);
            fclose($csvContent);

            // Guardar el archivo CSV en el storage de Laravel
            Storage::disk('s3')->put($carpeta . $fileName, $csvOutput);

            return response()->json([
                "message" => 'Archivo generado correctamente.',
                "status" => 200,
                "files" => $filenames
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al generar el archivo CSV.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function getCargasDiarias(Request $request)
    {
        try {
            if($request->fecha){
                $fechaCarbon = Carbon::parse($request->fecha, 'America/Mexico_City'); // Asumiendo que $request->fecha es una cadena de fecha válida

                $fecha = $fechaCarbon->subDay()->format('Y-m-d');
                $carpeta = $fechaCarbon->format('Y-m-d') . "/irge/";
            } else {
                $fecha = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d');
                $carpeta = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d')."/irge/";
            }

            $filenames = [];

            $entradas = DB::connection('mysql')
                ->table('entrada as E')
                ->join('grupos as G', 'E.grupo', '=', 'G.id')
                // ->join('subgrupos as SG', 'E.subgrupo', '=', 'SG.id')
                ->join('embarques as EQ', 'E.noEmbarque', '=', 'EQ.embarque')
                // ->join('companias as c', 'E.compania', '=', 'c.id')
                ->select(
                    // 'E.id',
                    'E.pg',
                    'E.nombrePorteador as transportista',
                    'G.nombre as grupo',
                    'E.nombreDestinatario as destino',
                    'E.noEmbarque',
                    'E.masa',
                    'E.densidad',
                    'E.volumen',
                    DB::raw("CONCAT(E.magnatel, ' %') AS porcentaje"),
                    'E.presionTanque',
                    DB::raw("DATE_FORMAT(EQ.inicioCarga_llenado, '%H:%i') AS inicio"),
                    DB::raw("DATE_FORMAT(EQ.finCarga_llenado, '%H:%i') AS fin"),
                    // 'E.subgrupo as id_subgrupo',
                    // 'SG.nombre as subgrupo',
                    // 'c.nombre as compania'
                )
                ->where('E.fechaJornada', $fecha)
                ->orderBy('E.id')
                ->get()
                ->toArray();

            $fileName = 'reporte_cargas_diarias_' . $fecha . '.csv';
            $filenames [] = $fileName;

            // Crear contenido del CSV en memoria
            $csvContent = fopen('php://temp', 'r+');

            // Encabezados del CSV
            $headers = [
                'pg',
                'transportista',
                'grupo',
                'destino',
                'noEmbarque',
                'masa',
                'densidad',
                'volumen',
                'porcentaje',
                'presionTanque',
                'inicio',
                'fin',
            ];
            fputcsv($csvContent, $headers);

            // Agregar los datos al contenido del CSV
            foreach ($entradas as $row) {
                fputcsv($csvContent, (array) $row);
            }

            // Rebobinar el contenido del CSV y obtenerlo como string
            rewind($csvContent);
            $csvOutput = stream_get_contents($csvContent);
            fclose($csvContent);

            // Guardar el archivo CSV en el storage de Laravel
            Storage::disk('s3')->put($carpeta . $fileName, $csvOutput);

            return response()->json([
                "message" => 'Archivo generado correctamente.',
                "status" => 200,
                "files" => $filenames
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al generar el archivo CSV.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function getRDC(Request $request)
    {
        try {
            if($request->fecha){
                $fechaCarbon = Carbon::parse($request->fecha, 'America/Mexico_City'); // Asumiendo que $request->fecha es una cadena de fecha válida

                $fecha = $fechaCarbon->subDay()->format('Y-m-d');
                $carpeta = $fechaCarbon->format('Y-m-d') . "/irge/";
            } else {
                $fecha = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d');
                $carpeta = Carbon::now('America/Mexico_City')->subDay()->format('Y-m-d')."/irge/";
            }

            $filenames = [];

            $entradas = DB::connection('mysql')
                ->table('entrada as e')
                ->join('embarques as emb', 'e.NoEmbarque', '=', 'emb.embarque')
                ->join('subgrupos as s', 'e.subgrupo', '=', 's.id')
                ->join('companias as c', 'e.compania', '=', 'c.id')
                ->select(
                    'e.pg',
                    'e.nombrePorteador',
                    'c.nombre AS compania',
                    's.nombre AS subgrupo',
                    'e.nombreDestinatario',
                    'e.noEmbarque',
                    'e.masa',
                    'emb.densidad_llenado as densidad',
                    'e.densidad AS densidad20',
                    'emb.volumen_llenado AS volumen',
                    'e.volumen AS volumen20',
                    DB::raw('ROUND((e.masa / e.densidad)) AS litros'),
                    DB::raw('ROUND((e.masa / e.densidad) * 0.264172, 2) AS galones'),
                    DB::raw("CONCAT(e.magnatel, '%') AS magnatel"),
                    DB::raw('FORMAT(e.presionTanque, 1, 0) AS presionTanque'),
                    DB::raw("IFNULL((SELECT DATE_FORMAT(fechaLlegada, '%H:%i') FROM accesos WHERE embarque = e.noEmbarque LIMIT 1), '') AS fechaLlegada"),
                    DB::raw("DATE_FORMAT(emb.inicioCarga_llenado, '%H:%i') as inicioCarga"),
                    DB::raw("DATE_FORMAT(emb.finCarga_llenado, '%H:%i') as finCarga"),
                    DB::raw("DATE_FORMAT(e.fechaSalida, '%H:%i') as fechaDoc"),
                    DB::raw('TIMEDIFF(e.fechaSalida, emb.finCarga_llenado) AS diferenciaTiempo'),
                )
                ->where('e.fechaJornada', $fecha)
                ->orderBy('e.id', 'asc')
                ->get()
                ->toArray();

            $fileName = 'reporte_rdc_' . $fecha . '.csv';
            $filenames [] = $fileName;

            // Crear contenido del CSV en memoria
            $csvContent = fopen('php://temp', 'r+');

            // Encabezados del CSV
            $headers = [
                'pg',
                'nombrePorteador',
                'compania',
                'subgrupo',
                'nombreDestinatario',
                'noEmbarque',
                'masa',
                'densidad',
                'densidad20',
                'volumen',
                'volumen20',
                'litros',
                'galones',
                'magnatel',
                'presionTanque',
                'fechaLlegada',
                'inicioCarga',
                'finCarga',
                'fechaDoc',
                'diferenciaTiempo',
                'fechaSalida',
                'finCarga_llenado',
                'idCompania',
                'grupo',
                'idSubgrupo',
                'presion',
                'masaStr',
                'fechaJ',
            ];
            fputcsv($csvContent, $headers);

            // Agregar los datos al contenido del CSV
            foreach ($entradas as $row) {
                fputcsv($csvContent, (array) $row);
            }

            // Rebobinar el contenido del CSV y obtenerlo como string
            rewind($csvContent);
            $csvOutput = stream_get_contents($csvContent);
            fclose($csvContent);

            // Guardar el archivo CSV en el storage de Laravel
            Storage::disk('s3')->put($carpeta . $fileName, $csvOutput);

            return response()->json([
                "message" => 'Archivo generado correctamente.',
                "status" => 200,
                "files" => $filenames
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al generar el archivo CSV.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
