<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MallaController extends Controller
{
    public function index()
    {
        $dia = strtolower(Carbon::now('America/Santiago')->format('l'));
        $hoy = Carbon::now('America/Santiago')->toDateString();

        // 1. Traer todos los grupos ordenados desde la tabla `grupos`
        $gruposBD = DB::table('grupos')->orderBy('id')->get();

        // 2. Procesos ejecutados hoy
        $procesosHoy = DB::table('procesos')
            ->whereDate('created_at', $hoy)
            ->get()
            ->keyBy('id_proceso');

        // 3. Estados (con sus propiedades visuales)
        $estados = DB::table('estados_procesos')
            ->get()
            ->keyBy('nombre');

        // 4. Procesos definidos en la malla
        $nombresProcesos = DB::table('nombres_procesos')->get()->groupBy('grupo');

        // 5. Armar la estructura de grupos y procesos
        $grupos = $gruposBD->map(function ($grupo) use ($nombresProcesos, $procesosHoy, $estados, $dia) {
            $procesos = collect($nombresProcesos[$grupo->nombre] ?? [])->map(function ($p) use ($procesosHoy, $estados, $dia) {
                $ejecucion = $procesosHoy[$p->id_proceso] ?? null;
                $dias = json_decode($p->dias ?? '[]', true);
                $correHoy = in_array($dia, $dias);

                $estadoNombre = $ejecucion->estado ?? 'Pendiente';
                $estadoInfo = $estados[$estadoNombre] ?? (object)[
                    'nombre' => $estadoNombre,
                    'color_fondo' => '#ffffff',
                    'color_texto' => '#000000',
                    'borde_color' => '#000000',
                    'emoji' => '❔'
                ];

                return (object) [
                    'id_proceso' => $p->id_proceso,
                    'proceso' => $p->proceso,
                    'descripcion' => $p->descripcion,
                    'hora_programada' => $p->hora_programada,
                    'estado_nombre' => $estadoInfo->nombre,
                    'color_fondo' => $estadoInfo->color_fondo,
                    'color_texto' => $estadoInfo->color_texto,
                    'borde_color' => $estadoInfo->borde_color,
                    'emoji' => $estadoInfo->emoji,
                    'inicio' => $ejecucion->inicio ?? null,
                    'fin' => $ejecucion->fin ?? null,
                    'adm_inicio' => $ejecucion->adm_inicio ?? null,
                    'adm_fin' => $ejecucion->adm_fin ?? null,
                    'correo_inicio' => $ejecucion->correo_inicio ?? null,
                    'correo_fin' => $ejecucion->correo_fin ?? null,
                    'registro_id' => $ejecucion->id ?? null,
                    'corre_hoy' => $correHoy,
                ];
            });

            return [
                'nombre' => $grupo->nombre,
                'color' => $grupo->color ?? 'slate',
                'procesos' => $procesos,
            ];
        });

        return view('procesos.malla', compact('grupos'));
    }

    public function actualizar(Request $request, $idProceso)
    {
        $correo = Auth::user()->email;
        $sigla = DB::table('operadores')->where('correo', $correo)->value('sigla') ?? 'ND';
        $hoy = Carbon::now('America/Santiago')->toDateString();

        // Buscar ejecución actual
        $registro = DB::table('procesos')
            ->where('id_proceso', $idProceso)
            ->whereDate('created_at', $hoy)
            ->first();

        // Obtener ID del estado seleccionado
        $estadoNombre = $request->input('estado');
        $estadoID = DB::table('estados_procesos')->where('nombre', $estadoNombre)->value('id');

        // Obtener nombre del grupo
        $grupoNombre = DB::table('nombres_procesos')->where('id_proceso', $idProceso)->value('grupo');

        // Armar campos
        $data = [];

        if ($request->filled('inicio')) {
            $data['inicio'] = $request->input('inicio');
            $data['adm_inicio'] = $sigla;
            $data['correo_inicio'] = $correo;
            $data['estado_id'] = DB::table('estados_procesos')->where('nombre', 'En ejecución')->value('id');
        }

        if ($request->filled('fin')) {
    $data['fin'] = $request->input('fin');
    $data['adm_fin'] = $sigla;
    $data['correo_fin'] = $correo;
    $data['estado_id'] = $estadoID;

    // Calcular total en formato HH:MM:SS
    if ($request->filled('inicio')) {
        $inicio = Carbon::parse($request->input('inicio'));
        $fin = Carbon::parse($request->input('fin'));
        $data['total'] = $inicio->diff($fin)->format('%H:%I:%S');
    }
}

        if (!empty($data)) {
            $data['updated_at'] = now();

            if ($registro) {
                DB::table('procesos')
                    ->where('id', $registro->id)
                    ->update($data);
            } else {
                $data = array_merge($data, [
                    'id_proceso' => $idProceso,
                    'grupo' => $grupoNombre,
                    'created_at' => now(),
                ]);

                DB::table('procesos')->insert($data);
            }
        }

        return redirect()->back()->with('success', 'Proceso actualizado.');
    }
}
