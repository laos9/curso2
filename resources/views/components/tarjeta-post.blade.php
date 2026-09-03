@props(['post'])

<article class="bg-white rounded-2xl shadow hover:shadow-lg transition p-6">
    <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full mb-2">
        {{ $post->categoria->nombre }}

    </span>
    <h2 class="text-xl font-semibold text-gray-900">{{ $post->titulo }}</h2>
    <p class="text-gray-600 mt-2">{{ Str::limit($post->contenido, 90) }}</p>
    <p class="text-gray-400 text-xs mt-4">{{ $post->created_at->format('d/m/Y') }}
        <a href="{{ route('avisos.edit', $post) }}" class="text-blue-700 text-sm font-semibold hover:underline mt-4 inline-block">Editar</a>

</p>
<form method="POST" action="{{ route('avisos.destroy', $post) }}" class="inline"
      onsubmit="return confirm('¿Borrar este aviso?')">
    @csrf
    @method('DELETE')
    <button class="text-red-600 text-sm font-semibold hover:underline">Borrar</button>
</form>

</article>
