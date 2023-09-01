<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CargasController extends Controller
{
    public function getCargas()
    {
        $data = DB::connection('sqlsrv')->select('SELECT * FROM Pesos');
        dd($data);

    }
}
