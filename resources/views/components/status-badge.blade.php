{{-- Colored badge that translates order/event/ticket status codes into readable labels. --}}
@props(['status'])
@php
$labels = [
    'pending' => __('Pending payment'),
    'paid' => __('Paid'),
    'cancelled' => __('Cancelled'),
    'expired' => __('Expired'),
    'published' => __('Published'),
    'draft' => __('Draft'),
    'archived' => __('Archived'),
    'valid' => __('Active ticket'),
    'used' => __('Used')
];
@endphp
<span {{ $attributes->class(['badge', 'badge-'.$status]) }}>{{ $labels[$status] ?? $status }}</span>