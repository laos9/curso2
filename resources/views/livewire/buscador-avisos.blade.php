<div>
    <input wire:model.live="busqueda" placeholder="Buscar aviso">
    <button wire:click="limpiar">Limpiar</button>
    @foreach ($avisos as $aviso)
        <p>{{ $aviso->titulo }}</p>
    @endforeach
</div>