<nav class="akila-pagination" aria-label="Paginación de productos">
    <p>Mostrando {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} productos</p>
    <div>
        @if($paginator->onFirstPage())<span aria-disabled="true">Anterior</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>@endif
        @foreach($elements as $element)
            @if(is_string($element))<span>{{ $element }}</span>@else
                @foreach($element as $page => $url)
                    @if($page === $paginator->currentPage())<span aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}" aria-label="Ir a página {{ $page }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach
        @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>@else<span aria-disabled="true">Siguiente</span>@endif
    </div>
</nav>
