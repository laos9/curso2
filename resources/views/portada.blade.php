@extends('layouts.publico')

@section('titulo', 'Portada · Blog de Avisos')

@section('contenido')
    <div class="bg-blue-950 text-center py-16 px-8">
        <h1 class="text-4xl font-bold text-white">Blog de Avisos de la Corporación</h1>
        <p class="text-blue-200 mt-3 text-lg">Avisos, operativos y noticias internas</p>
        <a href="{{ route('avisos.create') }}"
   class="inline-block mt-4 bg-white text-blue-950 font-semibold rounded-lg px-4 py-2 hover:bg-blue-100 transition">
    Nuevo aviso
</a>

    </div>

    <div class="max-w-4xl mx-auto p-8">
        <div class="grid md:grid-cols-2 gap-4">
            @foreach ($posts as $post)
                <x-tarjeta-post :post="$post" />

            @endforeach
        </div>
    </div>
@endsection
