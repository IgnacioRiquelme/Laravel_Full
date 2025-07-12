<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;

class RequerimientoController extends Controller
{
    // Mostrar formulario de ingreso
    public function create()
    {
        $fechaHora = Carbon::now('America/Santiago');
        $hora = $fechaHora->format('H:i');

        $turno = match (true) {
            $hora >= '08:00' && $hora <= '11:59' => 'mañana',
            $hora >= '12:00' && $hora <= '17:59' => 'tarde',
            default => 'noche',
        };

        return view('requerimientos.create', [
            'requerimiento' => null,
            'fechaHora' => $fechaHora->format('Y-m-d H:i:s'),
            'turno' => $turno,
            'tiposRequerimientos' => DB::table('tipos_requerimientos')->get(),
            'tiposSolicitantes' => DB::table('tipos_solicitantes')->get(),
            'tiposNegocios' => DB::table('tipos_negocios')->get(),
            'tiposAmbientes' => DB::table('tipos_ambientes')->get(),
            'tiposCapas' => DB::table('tipos_capas')->get(),
            'tiposServidores' => DB::table('tipos_servidores')->get(),
            'tiposEstados' => DB::table('tipos_estados')->get(),
            'tiposSolicitudes' => DB::table('tipos_solicitudes')->get(),
            'tiposPases' => DB::table('tipos_pases')->get(),
            'tiposICs' => DB::table('tipos_ics')->get(),
        ]);
    }
    // Exportar requerimientos filtrados a Excel
    public function exportarExcel(Request $request)
{
    $query = DB::table('requerimientos');

    // Filtro por fecha (si ambas fechas están presentes y tienen valores)
    if ($request->has('fecha_desde') && $request->has('fecha_hasta') &&
        !empty($request->input('fecha_desde')[0]) && !empty($request->input('fecha_hasta')[0])) {
        $desde = min($request->input('fecha_desde'));
        $hasta = max($request->input('fecha_hasta'));
        $query->whereBetween('fecha_hora', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
    }

    // Filtros múltiples (solo aplicar si hay valores reales)
    $filtros = [
        'filtro_numero' => 'numero_ticket',
        'filtro_tipo' => 'requerimiento',
        'filtro_negocio' => 'negocio',
        'filtro_ambiente' => 'ambiente',
        'filtro_capa' => 'capa',
        'filtro_servidor' => 'servidor',
        'filtro_estado' => 'estado',
        'filtro_tipo_solicitud' => 'tipo_solicitud',
        'filtro_tipo_pase' => 'tipo_pase',
        'filtro_ic' => 'ic',
    ];

    foreach ($filtros as $campoRequest => $campoDB) {
        $valores = $request->input($campoRequest);
        if (is_array($valores) && count(array_filter($valores)) > 0) {
            $query->whereIn($campoDB, array_filter($valores));
        }
    }

    $requerimientos = $query->orderBy('fecha_hora', 'desc')->get();

    return Excel::download(new class($requerimientos) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
        private $data;
        public function __construct($data) { $this->data = $data; }
        public function collection() { return collect($this->data); }
        public function headings(): array {
            return [
                'ID', 'Fecha Hora', 'Turno', 'N° Ticket', 'Requerimiento', 'Solicitante', 'Negocio', 'Ambiente',
                'Capa', 'Servidor', 'Estado', 'Tipo Solicitud', 'Tipo Pase', 'IC', 'Observaciones',
                'Creado Por', 'Creado', 'Actualizado'
            ];
        }
    }, 'requerimientos.xlsx');
}

    // Guardar nuevo requerimiento
    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_ticket' => 'required|unique:requerimientos,numero_ticket',
            'requerimiento' => 'required|string',
            'solicitante' => 'required|string',
            'negocio' => 'required|string',
            'ambiente' => 'required|string',
            'capa' => 'required|string',
            'servidor' => 'required|string',
            'estado' => 'required|string',
            'tipo_solicitud' => 'required|string',
            'tipo_pase' => 'required|string',
            'ic' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        $fechaHora = Carbon::now('America/Santiago');
        $hora = $fechaHora->format('H:i');

        $turno = match (true) {
            $hora >= '08:00' && $hora <= '11:59' => 'mañana',
            $hora >= '12:00' && $hora <= '17:59' => 'tarde',
            default => 'noche',
        };

        DB::table('requerimientos')->insert([
            'fecha_hora' => $fechaHora->format('Y-m-d H:i:s'),
            'turno' => $turno,
            'numero_ticket' => $validated['numero_ticket'],
            'requerimiento' => $validated['requerimiento'],
            'solicitante' => $validated['solicitante'],
            'negocio' => $validated['negocio'],
            'ambiente' => $validated['ambiente'],
            'capa' => $validated['capa'],
            'servidor' => $validated['servidor'],
            'estado' => $validated['estado'],
            'tipo_solicitud' => $validated['tipo_solicitud'],
            'tipo_pase' => $validated['tipo_pase'],
            'ic' => $validated['ic'],
            'observaciones' => $validated['observaciones'],
            'creado_por' => Auth::user()->name,
        ]);

        return redirect()->back()->with('success', 'Requerimiento ingresado correctamente.');
    }

    // Mostrar formulario de edición
    public function edit($ticket)
    {
        $requerimiento = DB::table('requerimientos')->where('numero_ticket', $ticket)->first();
        if (!$requerimiento) abort(404);

        return view('requerimientos.create', [
            'requerimiento' => $requerimiento,
            'fechaHora' => $requerimiento->fecha_hora,
            'turno' => $requerimiento->turno,
            'tiposRequerimientos' => DB::table('tipos_requerimientos')->get(),
            'tiposSolicitantes' => DB::table('tipos_solicitantes')->get(),
            'tiposNegocios' => DB::table('tipos_negocios')->get(),
            'tiposAmbientes' => DB::table('tipos_ambientes')->get(),
            'tiposCapas' => DB::table('tipos_capas')->get(),
            'tiposServidores' => DB::table('tipos_servidores')->get(),
            'tiposEstados' => DB::table('tipos_estados')->get(),
            'tiposSolicitudes' => DB::table('tipos_solicitudes')->get(),
            'tiposPases' => DB::table('tipos_pases')->get(),
            'tiposICs' => DB::table('tipos_ics')->get(),
        ]);
    }

    // Actualizar requerimiento existente
    public function update(Request $request, $ticket)
{
    $req = DB::table('requerimientos')->where('numero_ticket', $ticket)->first();

    if (!$req) {
        return redirect()->route('requerimientos.create')->with('error', 'Requerimiento no encontrado.');
    }

        // Validar sin duplicar ticket (no permitimos cambiar el número)
        $validated = $request->validate([
            'requerimiento' => 'required|string',
            'solicitante' => 'required|string',
            'negocio' => 'required|string',
            'ambiente' => 'required|string',
            'capa' => 'required|string',
            'servidor' => 'required|string',
            'estado' => 'required|string',
            'tipo_solicitud' => 'required|string',
            'tipo_pase' => 'required|string',
            'ic' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        DB::table('requerimientos')
            ->where('numero_ticket', $ticket)
            ->update(array_merge($validated, ['updated_at' => now()]));

        return redirect()->route('requerimientos.create')->with('success', '¡Requerimiento actualizado correctamente!');
    }

    // Mostrar requerimientos del día
    public function vistaRequerimientosDelDia()
    {
        $hoy = Carbon::now('America/Santiago')->toDateString();

        $requerimientos = DB::table('requerimientos')
            ->whereDate('fecha_hora', $hoy)
            ->orderBy('fecha_hora', 'desc')
            ->get();

        return view('requerimientos.dia', compact('requerimientos'));
    }

    // Guardar nuevo solicitante vía AJAX
    public function storeSolicitante(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:tipos_solicitantes,nombre',
        ]);

        DB::table('tipos_solicitantes')->insert([
            'nombre' => $request->input('nombre'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Solicitante agregado con éxito.']);
    }

    // Mostrar requerimientos filtrados
public function filtrados(Request $request)
{
    $query = DB::table('requerimientos');

    // Fecha (solo si ambos están presentes y no vacíos)
    if ($request->has('fecha_desde') && $request->has('fecha_hasta') &&
        !empty($request->input('fecha_desde')[0]) && !empty($request->input('fecha_hasta')[0])) {
        $desde = min($request->input('fecha_desde'));
        $hasta = max($request->input('fecha_hasta'));
        $query->whereBetween('fecha_hora', [$desde . ' 00:00:00', $hasta . ' 23:59:59']);
    }

    // Filtros seguros
    $filtros = [
        'filtro_numero' => 'numero_ticket',
        'filtro_tipo' => 'requerimiento',
        'filtro_negocio' => 'negocio',
        'filtro_ambiente' => 'ambiente',
        'filtro_capa' => 'capa',
        'filtro_servidor' => 'servidor',
        'filtro_estado' => 'estado',
        'filtro_tipo_solicitud' => 'tipo_solicitud',
        'filtro_tipo_pase' => 'tipo_pase',
        'filtro_ic' => 'ic',
    ];

    foreach ($filtros as $campoRequest => $campoDB) {
        $valores = $request->input($campoRequest);
        if (is_array($valores) && count(array_filter($valores)) > 0) {
            $query->whereIn($campoDB, array_filter($valores));
        }
    }

    $requerimientos = $query->orderBy('fecha_hora', 'desc')->get();

    return view('requerimientos.filtrados', [
        'requerimientos' => $requerimientos,
        'filtros' => $request->all(),
    ]);
}

}