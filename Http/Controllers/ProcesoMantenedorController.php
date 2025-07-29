<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proceso;

class ProcesoMantenedorController extends Controller
{
    public function index()
    {
        $procesos = Proceso::orderBy('id', 'desc')->get();
        return view('procesos.mantenedor.index', compact('procesos'));
    }

    public function create()
    {
        return view('procesos.mantenedor.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'trabajo' => 'required|string|max:255',
            'schedulix_id' => 'nullable|string|max:255',
        ]);

        Proceso::create($request->all());

        return redirect()->route('procesos.mantenedor.index')->with('success', 'Proceso creado correctamente');
    }

    public function edit($id)
    {
        $proceso = Proceso::findOrFail($id);
        return view('procesos.mantenedor.edit', compact('proceso'));
    }

    public function update(Request $request, $id)
    {
        $proceso = Proceso::findOrFail($id);

        $request->validate([
            'trabajo' => 'required|string|max:255',
            'schedulix_id' => 'nullable|string|max:255',
        ]);

        $proceso->update($request->all());

        return redirect()->route('procesos.mantenedor.index')->with('success', 'Proceso actualizado correctamente');
    }
}