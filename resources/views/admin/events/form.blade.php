<x-layout :title="$event->exists ? 'Edit event' : 'Buat event'" :admin="true">
    <section class="shell page-space"><a class="back-link" href="{{ route('admin.events.index') }}">← Event & tiket</a>
        <div class="section-heading">
            <div>
                <p class="eyebrow">EVENT EDITOR</p>
                <h1>{{ $event->exists ? 'Atur eventmu.' : 'Mulai cerita baru.' }}</h1>
                <p class="muted">Isi informasi event, lalu atur pilihan tiket dan kuotanya.</p>
            </div>@if($event->exists && $event->status === 'published')<a class="button secondary" href="{{ route('events.show', $event->slug) }}">Lihat halaman event ↗</a>@endif
        </div>
        <form class="admin-form" action="{{ $event->exists ? route('admin.events.update',$event) : route('admin.events.store') }}" method="post" enctype="multipart/form-data">@csrf @if($event->exists) @method('PUT') @endif
            <div class="panel">
                <h2>Informasi event</h2><x-field label="Judul event" name="title" :value="$event->title" required maxlength="160" /><x-field label="Alamat halaman / slug (huruf, angka, tanda hubung)" name="slug" :value="$event->slug" placeholder="nama-konser-2026" required maxlength="180" /><label class="field"><span>Ringkasan singkat</span><textarea name="summary" rows="3" required maxlength="300">{{ old('summary',$event->summary) }}</textarea><small>Ditampilkan di bawah judul event. Maksimal 300 karakter.</small></label><label class="field"><span>Deskripsi lengkap</span><textarea name="description" rows="7" required maxlength="20000">{{ old('description',$event->description) }}</textarea><small>Tulis informasi penampil, pengalaman, dan ketentuan dalam teks biasa.</small></label>
                <div class="form-row"><x-field label="Nama venue" name="venue" :value="$event->venue" required maxlength="160" /><x-field label="Kota" name="city" :value="$event->city" required maxlength="80" /></div><x-field label="Alamat lengkap" name="address" :value="$event->address" required maxlength="300" />
                <div class="form-row"><x-field label="Mulai (WIB)" name="start_time" type="datetime-local" :value="$event->start_time?->format('Y-m-d\TH:i')" required /><x-field label="Selesai (WIB, opsional)" name="end_time" type="datetime-local" :value="$event->end_time?->format('Y-m-d\TH:i')" /></div>
            </div>
            <div class="panel">
                <h2>Tampilan & publikasi</h2><label class="field"><span>Kategori event</span><select name="category_id">@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id',$event->category_id)==$category->id)>{{ $category->name }}</option>@endforeach</select></label><label class="field"><span>Status</span><select name="status">@foreach(['draft'=>'Draft','published'=>'Terbit','archived'=>'Arsip'] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$event->status ?? 'draft')===$value)>{{ $label }}</option>@endforeach</select></label><label class="check-field"><input type="checkbox" name="featured" value="1" @checked(old('featured',$event->featured))>Tampilkan sebagai event unggulan</label>@if($event->exists)<img src="{{ $event->banner_url }}" alt="Banner saat ini" class="rounded-lg mb-4" width="800" height="600">@endif<label class="field"><span>Banner utama</span><input type="file" name="banner_file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, WebP. Maks. 4 MB. Rasio anjuran 3:2.</small></label><label class="field"><span>Tambah galeri (opsional)</span><input type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp"><small>Maks. 6 gambar per unggahan, 4 MB per gambar.</small></label>@if($event->exists && $event->images->isNotEmpty())<div class="admin-gallery">@foreach($event->images as $image)<div><img src="{{ Storage::disk('public')->url($image->path) }}" alt="Galeri {{ $loop->iteration }}"><label class="field"><span>Urutan</span><input name="image_positions[{{ $image->id }}]" type="number" value="{{ $image->position }}" min="0" max="99"></label><label class="check-field"><input type="checkbox" name="remove_images[]" value="{{ $image->id }}">Hapus gambar</label></div>@endforeach</div>@endif<button class="button full">{{ $event->exists ? 'Simpan perubahan' : 'Buat event & lanjut atur tiket' }} →</button>
            </div>
        </form>
        @if($event->exists)<div id="ticket-section" data-refresh-target>
        <div class="section-heading mt-10">
            <div>
                <h2>Pilihan tiket</h2>
                <p class="muted">Nama, harga, dan kuota dapat diubah. Pesanan lama tetap memakai data saat pembelian.</p>
            </div><button class="button" type="button" data-open-modal="ticket-new">+ Tambah tiket</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Harga</th>
                        <th>Kuota</th>
                        <th>Tersedia</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>@forelse($event->ticketCategories as $ticket)<tr>
                        <td><strong>{{ $ticket->name }}</strong><small>{{ $ticket->description }}</small></td>
                        <td>Rp{{ number_format($ticket->price,0,',','.') }}</td>
                        <td>{{ $ticket->capacity }}</td>
                        <td>{{ $ticket->stock }}</td>
                        <td>{{ $ticket->active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <div class="table-actions"><button data-open-modal="ticket-{{ $ticket->id }}" type="button">Edit</button>
                                <form action="{{ route('admin.tickets.destroy',[$event,$ticket]) }}" method="post" data-fetch-form data-confirm="Hapus pilihan tiket? Jika sudah dibeli, tiket akan dinonaktifkan.">@csrf @method('DELETE')<button>Hapus</button></form>
                            </div>
                        </td>
                    </tr>@empty<tr>
                        <td colspan="6">Tambahkan pilihan tiket seperti VIP, CAT 1, atau Regular.</td>
                    </tr>@endforelse</tbody>
            </table>
        </div>
        @include('admin.events.ticket-modal',['ticket'=>new \App\Models\TicketCategory,'modalId'=>'ticket-new'])
        @foreach($event->ticketCategories as $ticket)@include('admin.events.ticket-modal',['ticket'=>$ticket,'modalId'=>'ticket-'.$ticket->id])@endforeach
        </div>
        @endif
    </section>
</x-layout>