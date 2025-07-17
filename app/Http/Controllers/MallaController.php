<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MallaProcesosExport;

class MallaController extends Controller
{
    public function index()
    {
        $fechaMalla = $this->obtenerFechaMalla();
        $dia = strtolower(Carbon::parse($fechaMalla)->locale('es')->englishDayOfWeek);

        $gruposBD = DB::table('grupos')->orderBy('id')->get();

        $procesosHoy = DB::table('procesos')
            ->whereDate('fecha_malla', $fechaMalla)
            ->get()
            ->keyBy('id_proceso');

        $estados = DB::table('estados_procesos')->get();

        $nombresProcesos = DB::table('nombres_procesos')->get()->groupBy('grupo');

        $grupos = $gruposBD->map(function ($grupo) use ($nombresProcesos, $procesosHoy, $estados, $dia) {
            $procesos = collect($nombresProcesos[$grupo->nombre] ?? [])->map(function ($p) use ($procesosHoy, $estados, $dia) {
                $ejecucion = $procesosHoy[$p->id_proceso] ?? null;
                $dias = json_decode($p->dias ?? '[]', true);
                $correHoy = in_array($dia, $dias);

                $estadoInfo = $estados->firstWhere('id', $ejecucion->estado_id ?? null) ?? (object)[
                    'nombre' => 'Pendiente',
                    'color_fondo' => '#ffffff',
                    'color_texto' => '#000000',
                    'borde_color' => '#000000',
                    'emoji' => '❔',
                ];

                return (object)[
                    'id_proceso' => $p->id_proceso,
                    'proceso' => $p->proceso,
                    'descripcion' => $p->descripcion,
                    'hora_programada' => $p->hora_programada,
                    'estado_nombre' => $estadoInfo->nombre,
                    'color_fondo' => $estadoInfo->color_fondo,
                    'color_texto' => $estadoInfo->color_texto,
                    'borde_color' => $estadoInfo->borde_color,
                    'emoji' => $estadoInfo->emoji,
                    'inicio' => $ejecucion && $ejecucion->inicio ? Carbon::parse($ejecucion->inicio) : null,
                    'fin' => $ejecucion && $ejecucion->fin ? Carbon::parse($ejecucion->fin) : null,
                    'adm_inicio' => $ejecucion->adm_inicio ?? null,
                    'adm_fin' => $ejecucion->adm_fin ?? null,
                    'correo_inicio' => $ejecucion->correo_inicio ?? null,
                    'correo_fin' => $ejecucion->correo_fin ?? null,
                    'registro_id' => $ejecucion->id ?? null,
                    'corre_hoy' => $correHoy,
                    'mostrar_boton' => $correHoy || ($ejecucion && $ejecucion->inicio && !$ejecucion->fin),
                ];
            });

            return [
                'nombre' => $grupo->nombre,
                'color' => $grupo->color ?? 'slate',
                'procesos' => $procesos,
            ];
        });

        return view('procesos.malla', [
            'grupos' => $grupos,
            'fecha_malla' => $fechaMalla,
            'vista_historica' => false,
        ]);
    }

    public function actualizar(Request $request, $idProceso)
    {
        $correo = Auth::user()->email;
        $sigla = DB::table('operadores')->where('correo', $correo)->value('sigla') ?? 'ND';

        $fechaMalla = $request->input('fecha') ?? $this->obtenerFechaMalla();

        $registro = DB::table('procesos')
            ->where('id_proceso', $idProceso)
            ->whereDate('fecha_malla', $fechaMalla)
            ->first();

        $estadoNombre = $request->input('estado');
        $estadoID = DB::table('estados_procesos')->where('nombre', $estadoNombre)->value('id');
        $grupoNombre = DB::table('nombres_procesos')->where('id_proceso', $idProceso)->value('grupo');

        $data = [
            'estado_id' => $estadoID,
            'updated_at' => now(),
        ];

        if ($request->filled('inicio')) {
            $data['inicio'] = $request->input('inicio');
            $data['adm_inicio'] = $sigla;
            $data['correo_inicio'] = $correo;

            if (!$request->filled('fin')) {
                $data['estado_id'] = DB::table('estados_procesos')->where('nombre', 'En ejecución')->value('id');
            }
        }

        if ($request->filled('fin')) {
            $data['fin'] = $request->input('fin');
            $data['adm_fin'] = $sigla;
            $data['correo_fin'] = $correo;

            if ($request->filled('inicio')) {
                $inicio = Carbon::parse($request->input('inicio'));
                $fin = Carbon::parse($request->input('fin'));
                $data['total'] = $inicio->diff($fin)->format('%H:%I:%S');
            }
        }

        if ($registro) {
            DB::table('procesos')->where('id', $registro->id)->update($data);
        } else {
            DB::table('procesos')->insert(array_merge($data, [
                'id_proceso' => $idProceso,
                'grupo' => $grupoNombre,
                'fecha_malla' => $fechaMalla,
                'created_at' => now(),
            ]));
        }

        return redirect()->back()->with('success', "✅ Proceso actualizado para el día $fechaMalla.");
    }

    public function cerrarDia(Request $request)
    {
        $hoy = Carbon::now('America/Santiago');
        $nuevaFecha = $hoy->hour < 9 ? $hoy->toDateString() : $hoy->copy()->addDay()->toDateString();
        $diaSemana = strtolower(Carbon::parse($nuevaFecha)->englishDayOfWeek);

        $procesosDelDia = DB::table('nombres_procesos')->get()->filter(function ($p) use ($diaSemana) {
            $dias = json_decode($p->dias ?? '[]', true);
            return in_array($diaSemana, $dias);
        });

        $insertados = 0;
        foreach ($procesosDelDia as $proceso) {
            $existe = DB::table('procesos')->where([
                ['id_proceso', '=', $proceso->id_proceso],
                ['fecha_malla', '=', $nuevaFecha],
            ])->exists();

            if (!$existe) {
                DB::table('procesos')->insert([
                    'id_proceso' => $proceso->id_proceso,
                    'grupo' => $proceso->grupo,
                    'fecha_malla' => $nuevaFecha,
                    'estado_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $insertados++;
            }
        }

        Storage::put('malla_fecha.txt', $nuevaFecha);
        return redirect()->back()->with('success', "✅ Cierre de día exitoso. Se registraron $insertados procesos para la fecha $nuevaFecha.");
    }

    private function obtenerFechaMalla(): string
    {
        if (Storage::exists('malla_fecha.txt')) {
            return trim(Storage::get('malla_fecha.txt'));
        } else {
            $hoy = Carbon::now('America/Santiago');
            $fecha = $hoy->hour < 9 ? $hoy->copy()->subDay()->toDateString() : $hoy->toDateString();
            Storage::put('malla_fecha.txt', $fecha);
            return $fecha;
        }
    }

    public function historico($fecha)
    {
        try {
            $fecha = Carbon::parse($fecha)->toDateString();
        } catch (\Exception $e) {
            abort(404);
        }

        $dia = strtolower(Carbon::parse($fecha)->format('l'));
        $gruposBD = DB::table('grupos')->orderBy('id')->get();
        $procesosDelDia = DB::table('procesos')->whereDate('fecha_malla', $fecha)->get()->keyBy('id_proceso');
        $estados = DB::table('estados_procesos')->get();
        $nombresProcesos = DB::table('nombres_procesos')->get()->groupBy('grupo');

        $grupos = $gruposBD->map(function ($grupo) use ($nombresProcesos, $procesosDelDia, $estados, $dia) {
            $procesos = collect($nombresProcesos[$grupo->nombre] ?? [])->map(function ($p) use ($procesosDelDia, $estados, $dia) {
                $ejecucion = $procesosDelDia[$p->id_proceso] ?? null;
                $dias = json_decode($p->dias ?? '[]', true);
                $correHoy = in_array($dia, $dias);

                $estadoInfo = $estados->firstWhere('id', $ejecucion->estado_id ?? null) ?? (object)[
                    'nombre' => 'Pendiente',
                    'color_fondo' => '#ffffff',
                    'color_texto' => '#000000',
                    'borde_color' => '#000000',
                    'emoji' => '❔',
                ];

                return (object)[
                    'id_proceso' => $p->id_proceso,
                    'proceso' => $p->proceso,
                    'descripcion' => $p->descripcion,
                    'hora_programada' => $p->hora_programada,
                    'estado_nombre' => $estadoInfo->nombre,
                    'color_fondo' => $estadoInfo->color_fondo,
                    'color_texto' => $estadoInfo->color_texto,
                    'borde_color' => $estadoInfo->borde_color,
                    'emoji' => $estadoInfo->emoji,
                    'inicio' => !empty($ejecucion?->inicio) ? Carbon::parse($ejecucion->inicio) : null,
                    'fin' => !empty($ejecucion?->fin) ? Carbon::parse($ejecucion->fin) : null,
                    'adm_inicio' => $ejecucion->adm_inicio ?? null,
                    'adm_fin' => $ejecucion->adm_fin ?? null,
                    'correo_inicio' => $ejecucion->correo_inicio ?? null,
                    'correo_fin' => $ejecucion->correo_fin ?? null,
                    'registro_id' => $ejecucion->id ?? null,
                    'corre_hoy' => $correHoy,
                    'mostrar_boton' => true,
                ];
            });

            return [
                'nombre' => $grupo->nombre,
                'color' => $grupo->color ?? 'slate',
                'procesos' => $procesos,
            ];
        });

        return view('procesos.malla', [
            'grupos' => $grupos,
            'fecha_malla' => $fecha,
            'vista_historica' => true,
        ]);
    }

    public function exportar($fecha)
    {
        $fechaFormateada = Carbon::parse($fecha)->toDateString();

        $procesos = DB::table('procesos')
            ->whereDate('fecha_malla', $fechaFormateada)
            ->join('nombres_procesos', 'procesos.id_proceso', '=', 'nombres_procesos.id_proceso')
            ->leftJoin('estados_procesos', 'procesos.estado_id', '=', 'estados_procesos.id')
            ->select(
                'procesos.id_proceso',
                'nombres_procesos.proceso',
                'nombres_procesos.descripcion',
                'procesos.inicio',
                'procesos.fin',
                'procesos.total',
                'procesos.adm_inicio',
                'procesos.adm_fin',
                'estados_procesos.nombre as estado',
                'procesos.correo_inicio',
                'procesos.correo_fin',
                'nombres_procesos.grupo'
            )
            ->orderBy('nombres_procesos.grupo')
            ->orderBy('procesos.id_proceso')
            ->get()
            ->map(function ($proceso) {
    $proceso->inicio = $proceso->inicio ? Carbon::parse($proceso->inicio) : null;
    $proceso->fin = $proceso->fin ? Carbon::parse($proceso->fin) : null;
    $proceso->grupo = (string) $proceso->grupo; // 👈 Forzar grupo como string
    return $proceso;
});

        return Excel::download(new MallaProcesosExport($procesos, $fechaFormateada), "Bitacora-Procesos-{$fechaFormateada}.xlsx");
    }
}
