<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Incidente;
use Illuminate\Support\Facades\DB;

class IncidenteController extends Controller
{
    public function create()
{
    $procesos = DB::table('procesos_mantenimiento')
    ->select('id', 'schedulix_id', 'trabajo', 'ruta_completa', 'responsable_escalamiento', 'requiere_solucion_inmediata')
    ->get();
    $estados = DB::table('estado_incidentes')->get();
    $negocios = DB::table('negocio_incidentes')->get();
    $ambientes = DB::table('ambiente_incidentes')->get();
    $capas = DB::table('capa_incidentes')->get();
    $servidores = DB::table('servidor_incidentes')->get();
    $eventos = DB::table('evento_incidentes')->get();
    $acciones = DB::table('accion_incidentes')->get();
    $escalados = DB::table('escalado_incidentes')->get();

    $incidentesHoy = DB::table('incidentes')
    ->join('estado_incidentes', 'incidentes.estado_incidente_id', '=', 'estado_incidentes.id')
    ->whereDate('incidentes.created_at', now())
    ->where('estado_incidentes.nombre', '!=', 'Cerrado')
    ->select('incidentes.*', 'estado_incidentes.nombre as estado_nombre')
    ->get();

    return view('incidentes.create', compact(
        'procesos', 'estados', 'negocios', 'ambientes', 'capas', 'servidores', 'eventos', 'acciones', 'escalados', 'incidentesHoy'
    ));
}

    public function store(Request $request)
{
    $request->validate([
        'proceso_id'                => 'required|exists:procesos_mantenimiento,id',
        'negocio_incidente_id'      => 'required|exists:negocio_incidentes,id',
        'ambiente_incidente_id'     => 'required|exists:ambiente_incidentes,id',
        'capa_incidente_id'         => 'required|exists:capa_incidentes,id',
        'servidor_incidente_id'     => 'required|exists:servidor_incidentes,id',
        'evento_incidente_id'       => 'required|exists:evento_incidentes,id',
        'accion_incidente_id'       => 'required|exists:accion_incidentes,id',
        'escalado_incidente_id'     => 'required|exists:escalado_incidentes,id',
        'seguimiento'               => 'nullable|string|max:255',
        'estado_incidente_id'       => 'required|exists:estado_incidentes,id',
        'descripcion_evento'        => 'nullable|string',
        'solucion'                  => 'nullable|string',
        'observaciones'             => 'nullable|string',
        'inicio'                    => 'nullable|date',
        'registro'                  => 'nullable|date',
        'requerimiento'             => 'nullable|string|max:255',
    ]);

    $incidente = new Incidente();
    $incidente->proceso_id                = $request->proceso_id;
    $incidente->negocio_incidente_id      = $request->negocio_incidente_id;
    $incidente->ambiente_incidente_id     = $request->ambiente_incidente_id;
    $incidente->capa_incidente_id         = $request->capa_incidente_id;
    $incidente->servidor_incidente_id     = $request->servidor_incidente_id;
    $incidente->evento_incidente_id       = $request->evento_incidente_id;
    $incidente->accion_incidente_id       = $request->accion_incidente_id;
    $incidente->escalado_incidente_id     = $request->escalado_incidente_id;
    $incidente->seguimiento               = $request->seguimiento;
    $incidente->estado_incidente_id       = $request->estado_incidente_id;
    $incidente->descripcion_evento        = $request->descripcion_evento;
    $incidente->solucion                  = $request->solucion;
    $incidente->observaciones             = $request->observaciones;
    $incidente->inicio                    = $request->filled('inicio') ? $request->inicio : now();
    $incidente->registro                  = $request->filled('registro') ? $request->registro : now();
    $incidente->requerimiento             = $request->requerimiento;
    $incidente->creado_por                = Auth::user()->name; // 👈 Aquí se agrega el nombre del usuario logueado
    $incidente->save();

    return redirect()->route('incidentes.create')->with('success', 'Incidente guardado correctamente');
}
public function searchProceso(Request $request)
{
    $q = $request->input('q');
    $procesos = DB::table('procesos_mantenimiento')
        ->select('id', 'schedulix_id', 'trabajo', 'ruta_completa', 'responsable_escalamiento', 'requiere_solucion_inmediata')
        ->where('trabajo', 'like', "%$q%")
        ->orWhere('schedulix_id', 'like', "%$q%")
        ->limit(20)
        ->get();

    return response()->json($procesos);
}
}
