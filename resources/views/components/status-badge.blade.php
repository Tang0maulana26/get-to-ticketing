@props(['status'])
@php($labels = ['pending'=>'Menunggu pembayaran','paid'=>'Lunas','cancelled'=>'Dibatalkan','expired'=>'Kedaluwarsa','published'=>'Terbit','draft'=>'Draft','archived'=>'Diarsipkan','valid'=>'Tiket aktif','used'=>'Sudah digunakan'])
<span {{ $attributes->class(['badge', 'badge-'.$status]) }}>{{ $labels[$status] ?? $status }}</span>