@extends('layouts.app')

@section('title', 'Inicio - Sushi Akila')


@section('carrusel')
    <div class="container-fluid px-0">
        <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <!-- Indicadores -->
            <ol class="carousel-indicators">
                <li data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></li>
                <li data-bs-target="#heroCarousel" data-bs-slide-to="1"></li>
                <li data-bs-target="#heroCarousel" data-bs-slide-to="2"></li>
            </ol>

            <!-- Slides -->
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="{{ asset('img/slide1.png') }}" class="d-block w-100" alt="Slide 1">
                    <div class="carousel-caption d-none d-md-block">
                        <h2 class="display-5 fw-bold">Bienvenido a Sushi Akila</h2>
                        <p>Descubre nuestros sabores únicos</p>
                        <a href="{{ route('menu') }}" class="btn btn-light">Ver Menú</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="{{ asset('img/slide2.png') }}" class="d-block w-100" alt="Slide 2">
                    <div class="carousel-caption d-none d-md-block">
                        <h2 class="display-5 fw-bold">Promociones Especiales</h2>
                        <p>2×1 en Hand Rolls todos los martes</p>
                        <a href="{{ route('menu', ['section' => 'promociones']) }}" class="btn btn-light">Ver Promos</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="{{ asset('img/slide3.png') }}" class="d-block w-100" alt="Slide 3">
                    <div class="carousel-caption d-none d-md-block">
                        <h2 class="display-5 fw-bold">Calidad y Frescura</h2>
                        <p>Ingredientes seleccionados para ti</p>
                        <a href="{{ route('menu') }}" class="btn btn-light">Explorar</a>
                    </div>
                </div>
            </div>

            <!-- Controles -->
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>
    </div>
@endsection

@section('content')
    {{-- Sección de bienvenida --}}
    <div class="text-center my-12">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">¡Bienvenido a Sushi Akila!</h1>
        <p class="text-lg text-gray-700 mb-6">
            Disfruta de los mejores sabores japoneses en la comodidad de tu hogar.
        </p>
        <a href="{{ route('menu') }}"
            class="inline-block bg-red-500 hover:bg-red-600 text-white font-semibold py-3 px-6 rounded-lg transition">
            Ver Menú
        </a>
    </div>

    {{-- Aquí podrías agregar secciones destacadas, p.ej. favoritos, promociones, etc. --}}
    {{-- Ejemplo de sección “Favoritos” --}}
    <section class="container mx-auto px-4 my-12">
        <h2 class="text-2xl font-bold mb-6">Nuestros Favoritos</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- @foreach ($favoritos as $prod)
        <div class="bg-white rounded-lg shadow-md overflow-hidden flex flex-col">
          <img 
            src="{{ asset('storage/'.$prod->imagen) }}"
            alt="{{ $prod->nombre }}"
            class="h-48 w-full object-cover"
          >
          <div class="p-4 flex-grow">
            <h3 class="font-semibold text-lg">{{ $prod->nombre }}</h3>
            <p class="text-gray-600 mt-1">{{ Str::limit($prod->descripcion, 60) }}</p>
          </div>
          <div class="p-4 flex items-center justify-between">
            <span class="font-bold">${{ number_format($prod->precio,0,',','.') }}</span>
            <button
              @click="openModal('{{ $prod->id }}')"
              class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded transition"
            >
              Personalizar
            </button>
          </div>
        </div>
      @endforeach --}}
        </div>
    </section>
@endsection
