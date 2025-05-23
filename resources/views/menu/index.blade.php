@extends('layouts.app')
@section('content')

<!-- Navbar de categorías -->
<nav class="py-3 bg-light">
  <div class="container">
    <ul class="nav nav-pills overflow-auto">
      @foreach($categorias as $categoria)
        <li class="nav-item">
          <a class="nav-link {{ request('filter') == $categoria->slug ? 'active' : '' }}"
             href="{{ route('menu', ['filter' => $categoria->slug]) }}">
            {{ $categoria->nombre }}
          </a>
        </li>
      @endforeach
    </ul>
  </div>
</nav>

<!-- Grid de productos dinámico -->
<div class="container py-4">
  <div class="row g-4">
    @foreach($productos as $producto)
      <div class="col-12 col-sm-6 col-md-4">
        <div class="card h-100">
          <img src="{{ $producto->imagen ? asset('storage/' . $producto->imagen) : asset('images/placeholder.jpg') }}"
               class="card-img-top"
               alt="{{ $producto->nombre }}">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">{{ $producto->nombre }}</h5>
            <p class="card-text">{{ Str::limit($producto->descripcion, 60) }}</p>
            <div class="mt-auto d-grid gap-2">
              <p class="h5 mb-3">
                ${{ number_format($producto->precio, 0, ',', '.') }} CLP
              </p>

              <!-- Botón directo al carrito -->
              <form action="{{ route('cart.add') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $producto->id }}">
                <button class="btn btn-primary w-100">Agregar al carrito</button>
              </form>

              <!-- Botón de detalles (solo si es personalizable) -->
              @if($producto->personalizable)
                <button class="btn btn-outline-secondary w-100" data-bs-toggle="modal"
                        data-bs-target="#productModal-{{ $producto->id }}">
                  Personalizar producto
                </button>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
</div>

<!-- Modales de detalle y personalización -->
@foreach($productos->where('personalizable', true) as $producto)
  <div class="modal fade" id="productModal-{{ $producto->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ $producto->nombre }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <!-- Imagen producto -->
            <div class="col-md-6">
              <img src="{{ $producto->imagen ? asset('storage/' . $producto->imagen) : asset('images/placeholder.jpg') }}"
                   class="img-fluid" alt="{{ $producto->nombre }}">
            </div>
            <div class="col-md-6">
              <p class="h4" id="dynamicPrice-{{ $producto->id }}">
                ${{ number_format($producto->precio, 0, ',', '.') }}
              </p>

              <!-- Base: ingredientes base por defecto -->
              <div class="mb-3">
                <h6>Base</h6>
                <ul class="list-group">
                  @foreach($producto->ingredientes->where('categoria','base') as $ing)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      {{ $ing->nombre }}
                    </li>
                  @endforeach
                </ul>
              </div>

              <!-- Proteínas: por defecto y opción de cambio -->
              <div class="mb-3">
                <h6>Proteínas</h6>
                <ul class="list-group mb-2">
                  @foreach($producto->ingredientes->where('categoria','proteina') as $ing)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      {{ $ing->nombre }}
                      <span class="badge bg-secondary">Default</span>
                    </li>
                  @endforeach
                </ul>
                <div>
                  <p>Cambiar proteína (<small>+ $1.000 CLP</small>)</p>
                  @foreach($allIngredients->where('categoria','proteina') as $ing)
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1"
                            data-replace-type="proteina"
                            data-new-id="{{ $ing->id }}">
                      {{ $ing->nombre }}
                    </button>
                  @endforeach
                </div>
              </div>

              <!-- Vegetales: por defecto y opción de cambio -->
              <div class="mb-3">
                <h6>Vegetales</h6>
                <ul class="list-group mb-2">
                  @foreach($producto->ingredientes->where('categoria','vegetal') as $ing)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      {{ $ing->nombre }}
                      <span class="badge bg-secondary">Default</span>
                    </li>
                  @endforeach
                </ul>
                <div>
                  <p>Cambiar vegetal (<small>+ $1.000 CLP</small>)</p>
                  @foreach($allIngredients->where('categoria','vegetal') as $ing)
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1"
                            data-replace-type="vegetal"
                            data-new-id="{{ $ing->id }}">
                      {{ $ing->nombre }}
                    </button>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <form action="{{ route('cart.add') }}" method="POST" class="w-100">
            @csrf
            <input type="hidden" name="product_id" value="{{ $producto->id }}">
            <input type="hidden" name="price_adjustment" id="priceAdjustment-{{ $producto->id }}" value="0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary w-100">Agregar al carrito</button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endforeach

@endsection
