@extends('admin.layout')

@section('title', 'Dashboard')
@section('page-title', 'Panel de Control')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
  {{-- Tarjetas resumen --}}
  <div class="bg-white p-6 rounded shadow">
    <h3 class="text-sm font-medium text-gray-500">Pedidos Totales</h3>
    <p class="mt-2 text-3xl font-bold">{{ $totalOrders }}</p>
  </div>
  <div class="bg-white p-6 rounded shadow">
    <h3 class="text-sm font-medium text-gray-500">Ventas Hoy</h3>
    <p class="mt-2 text-3xl font-bold">${{ number_format($salesToday,0,',','.') }}</p>
  </div>
  <div class="bg-white p-6 rounded shadow">
    <h3 class="text-sm font-medium text-gray-500">Productos</h3>
    <p class="mt-2 text-3xl font-bold">{{ $totalProducts }}</p>
  </div>
  <div class="bg-white p-6 rounded shadow">
    <h3 class="text-sm font-medium text-gray-500">Ingredientes</h3>
    <p class="mt-2 text-3xl font-bold">{{ $totalIngredients }}</p>
  </div>
  <div class="bg-white p-6 rounded shadow md:col-span-2 lg:col-span-1">
    <h3 class="text-sm font-medium text-gray-500">Categorías</h3>
    <p class="mt-2 text-3xl font-bold">{{ $totalCategories }}</p>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
  {{-- Gráfico de Pedidos por Estado --}}
  <div class="bg-white p-6 rounded shadow">
    <h3 class="text-lg font-semibold mb-4">Pedidos por Estado</h3>
    <canvas id="statusChart"></canvas>
  </div>

  {{-- Tabla de Top Productos --}}
  <div class="bg-white p-6 rounded shadow">
    <h3 class="text-lg font-semibold mb-4">Top 5 Productos (Ingresos)</h3>
    <ul class="space-y-2">
      @foreach($topProducts as $item)
        <li class="flex justify-between">
          <span>{{ $item->producto->nombre }}</span>
          <span>${{ number_format($item->revenue,0,',','.') }}</span>
        </li>
      @endforeach
    </ul>
  </div>
</div>

{{-- Scripts de Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Datos para pedidos por estado
  const statusLabels = @json(array_keys($ordersByStatus->toArray()));
  const statusData   = @json(array_values($ordersByStatus->toArray()));

  new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
      labels: statusLabels,
      datasets: [{
        data: statusData,
        backgroundColor: ['#F59E0B','#10B981','#EF4444','#3B82F6']
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
</script>
@endsection
