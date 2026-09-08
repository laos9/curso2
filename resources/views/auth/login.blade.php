@extends('layouts.publico')

@section('titulo', 'Entrar')

@section('contenido')
    <form method="POST" action="/login" class="max-w-sm mx-auto p-8 bg-white rounded-lg shadow mt-12">
        @csrf

        <h1 class="text-xl font-semibold text-gray-900 mb-4">Entrar al blog</h1>

        <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
        <input name="email" type="email" value="{{ old('email') }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
        @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

        <label class="block text-sm font-medium text-gray-700 mb-1 mt-4">Contraseña</label>
        <input name="password" type="password"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none">
        @error('password') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror

        <button class="w-full bg-blue-900 text-white font-semibold rounded-lg py-2 hover:bg-blue-800 transition mt-5">
            Entrar
        </button>
    </form>
@endsection
