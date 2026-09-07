<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div class="bg-white border-b px-6 py-2 text-sm flex justify-end items-center gap-4">
    @auth
        <span class="text-gray-600">Hola, {{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-blue-700 font-semibold hover:underline">Salir</button>
        </form>
    @else
        <a href="{{ route('login') }}" class="text-blue-700 font-semibold hover:underline">Entrar</a>
    @endauth
</div>

    <title>@yield('titulo', 'Blog de Avisos')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    @yield('contenido')
</body>
</html>
