@extends('layouts.publico')

@section('titulo', 'Nuevo aviso')

@section('contenido')
    <form method="POST" action="{{ route('avisos.store') }}" class="max-w-lg mx-auto p-8 bg-white rounded-lg shadow mt-8">
        @csrf

        <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
        <input name="titulo" value="{{ old('titulo') }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
        @error('titulo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

        <label class="block text-sm font-medium text-gray-700 mb-1 mt-4">Categoría</label>
        <select name="categoria_id"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
            <option value="">Elige una categoría...</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>
                    {{ $categoria->nombre }}
                </option>
            @endforeach
        </select>
        @error('categoria_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

        <label class="block text-sm font-medium text-gray-700 mb-1 mt-4">Contenido</label>
        <textarea name="contenido" rows="4"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">{{ old('contenido') }}</textarea>
        @error('contenido') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

        <button class="w-full bg-blue-900 text-white font-semibold rounded-lg py-2 hover:bg-blue-800 transition mt-5">
            Publicar aviso
        </button>
    </form>
@endsection
