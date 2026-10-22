@props(['detail'])
<div class="space-y-1 text-sm">
    @if(filled($detail['Base'] ?? null))<p class="mb-1"><strong>Base:</strong> {{ $detail['Base'] }}</p>@endif
    @foreach($detail['Bloques'] ?? [] as $block)
        <p class="mb-1"><strong>{{ $block['etiqueta'] ?? 'Roll '.$loop->iteration }} · {{ $block['cantidad_etiqueta'] ?? $block['piezas'].' piezas' }}:</strong>
            {{ collect($block['grupos'])->flatMap(fn ($group) => array_column($group['opciones'], 'nombre'))->implode(' · ') }}
        </p>
    @endforeach
    @foreach($detail['Grupos'] ?? [] as $group)
        @if($group['opciones'])<p class="mb-1"><strong>{{ $group['nombre'] }}:</strong> {{ collect($group['opciones'])->pluck('nombre')->implode(', ') }}</p>@endif
    @endforeach
    @foreach($detail['Preparación'] ?? [] as $preparation)<p class="mb-1">{{ $preparation['nombre'] }}: {{ $preparation['piezas'] }} piezas en total.</p>@endforeach
    @foreach($detail['Sin base'] ?? [] as $name)<p class="mb-1">Sin {{ $name }}</p>@endforeach
</div>
