@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center bg-gray-100 dark:bg-gray-900 pt-12 min-h-screen">
    <h1 class="text-3xl font-bold text-center mb-6">Menú Analista</h1>

    <div class="flex flex-col gap-4 w-full max-w-sm">
        <a href="{{ route('requerimientos.create') }}" class="menu-item bg-indigo-500 hover:bg-indigo-600 text-white py-3 px-6 rounded text-center font-medium">Nuevo Requerimiento</a>
        <a href="{{ route('incidentes.create') }}"  class="menu-item bg-indigo-500 hover:bg-indigo-600 text-white py-3 px-6 rounded text-center font-medium">Nuevo Incidente</a>
        <a href="#" class="menu-item bg-indigo-500 hover:bg-indigo-600 text-white py-3 px-6 rounded text-center font-medium">Actualización de Incidentes</a>
        <a href="#" class="menu-item bg-indigo-500 hover:bg-indigo-600 text-white py-3 px-6 rounded text-center font-medium">Reporte Veeam y TCM</a>
        <a href="{{ route('procesos.malla') }}" class="menu-item bg-indigo-500 hover:bg-indigo-600 text-white py-3 px-6 rounded text-center font-medium">Bitácora de Procesos</a>
        <a href="#" class="menu-item bg-indigo-500 hover:bg-indigo-600 text-white py-3 px-6 rounded text-center font-medium">Registro de Incidentes Operacionales</a>
        <a href="{{ route('carga.requerimientos.form') }}" class="menu-item bg-indigo-500 hover:bg-indigo-600 text-white py-3 px-6 rounded text-center font-medium">📥 Cargar Requerimientos (Excel)</a>

        <a href="#" class="exit-button bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded text-center font-medium mt-4">Salir</a>
    </div>
</div>
@endsection


