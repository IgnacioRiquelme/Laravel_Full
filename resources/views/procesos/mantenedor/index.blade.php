{{-- filepath: resources/views/procesos/mantenedor/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto bg-white p-8 rounded-lg shadow-lg mt-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-indigo-600">Mantenedor de Procesos</h1>
        <a href="{{ route('procesos.mantenedor.create') }}" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600">Nuevo Proceso</a>
    </div>
    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    <table class="min-w-full table-auto border">
        <thead>
            <tr class="bg-indigo-100">
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Trabajo</th>
                <th class="px-4 py-2">Schedulix ID</th>
                <th class="px-4 py-2">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($procesos as $proceso)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $proceso->id }}</td>
                    <td class="px-4 py-2">{{ $proceso->trabajo }}</td>
                    <td class="px-4 py-2">{{ $proceso->schedulix_id ?? '-' }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('procesos.mantenedor.edit', $proceso->id) }}" class="text-blue-600 hover:underline">Editar</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection