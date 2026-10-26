@props(['detail'])
<div class="space-y-1 text-sm">
    @if(isset($detail['Cantidad incluida'], $detail['Unidad comercial']))
        @php($commercialUnit = \App\Enums\UnidadComercial::tryFrom($detail['Unidad comercial']))
        @if($commercialUnit)<p class="mb-1">Incluye: {{ $detail['Cantidad incluida'] }} {{ $commercialUnit->label((int) $detail['Cantidad incluida']) }}.</p>@endif
    @endif
    @if(filled($detail['Base'] ?? null))<p class="mb-1"><strong>Base:</strong> {{ $detail['Base'] }}</p>@endif
    @foreach($detail['Bloques'] ?? [] as $block)
        <p class="mb-1"><strong>{{ $block['etiqueta'] ?? 'Roll '.$loop->iteration }} · {{ $block['cantidad_etiqueta'] ?? $block['piezas'].' piezas' }}:</strong>
            {{ collect($block['grupos'])->flatMap(fn ($group) => array_column($group['opciones'], 'nombre'))->implode(' · ') }}
        </p>
    @endforeach
    @foreach($detail['Grupos'] ?? [] as $group)
        @if($group['opciones'])<p class="mb-1"><strong>{{ $group['nombre'] }}{{ ($group['mixto'] ?? false) ? ' (Mixto)' : '' }}:</strong> {{ collect($group['opciones'])->pluck('nombre')->implode(', ') }}</p>@endif
    @endforeach
    @foreach($detail['Preparación'] ?? [] as $preparation)<p class="mb-1">{{ $preparation['nombre'] }}: {{ $preparation['piezas'] }} piezas en total.</p>@endforeach
    @foreach($detail['Sin base'] ?? [] as $name)<p class="mb-1">Sin {{ $name }}</p>@endforeach
    @foreach($detail['Salsas incluidas'] ?? [] as $sauce)<p class="mb-1">Incluido: {{ $sauce['cantidad'] }} {{ $sauce['unidad'] }} de {{ $sauce['nombre'] }}.</p>@endforeach
</div>
