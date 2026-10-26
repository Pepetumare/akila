@props(['state'])
@php($label = \App\Services\KitchenWorkflow::LABELS[$state] ?? match($state) {'approved'=>'Aprobado','pending'=>'Pendiente','in_process'=>'En proceso','authorized'=>'Autorizado','rejected'=>'Rechazado','cancelled'=>'Cancelado','refunded'=>'Reembolsado','charged_back'=>'Contracargo',default=>$state ?: 'Sin verificar'})
@php($tone = in_array($state,['approved','listo','entregado']) ? 'green' : (in_array($state,['pendiente','rejected','cancelado','cancelled','charged_back']) ? 'red' : 'orange'))
<span class="badge {{ $tone }}">{{ $label }}</span>
