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
                    $claveLlenado = $fechaBD . $numeroLlenadera . "-" . $embfolio . $tipoTanque;
                    $pg = trim("PG-" . substr($cargaSCA->TankTruck, 2));
                    $entradaLlenado = "$cargaSCA->EntryYear-$cargaSCA->EntryMonth-$cargaSCA->EntryDay $cargaSCA->EntryTime:00";
                    $embarque = 0;
    
                    $inicioCarga = "$iniDate_year-$iniDate_month-$iniDate_day $cargaSCA->LoadIniTime:00";
                    //dd($inicioCarga);
                    $finCarga = "$endDate_year-$endDate_month-$endDate_day $cargaSCA->LoadEndTime:00";
                    //dd($finCarga);
                    
                    $fechaJornada = $fecha;
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
            //dd($cargasSCA);
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
                        'masaKgs_llenado' => round($masa * 1000),
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

            $fecha = Carbon::parse($request->fecha);
            $fechaIni = $fecha->format('Y-m-d') . ' 05:00:00';
            $fechaFin = $fecha->addDay(1)->format('Y-m-d') . ' 05:00:00';
            
            $cargas = [];

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
}
