<x-layout :title="$event->title">
    <div class="shell page-space">
        <a class="back-link" href="{{ route('catalog') }}#events">← Semua event</a>

        <div class="detail-grid">
            <div>
                <div class="detail-banner">
                    <img src="{{ $event->banner_url }}" alt="Poster {{ $event->title }}" width="800" height="600">
                </div>

                <div class="detail-heading">
                    <span class="badge badge-published">{{ $event->category->name }}</span>
                    <h1>{{ $event->title }}</h1>
                    <p class="lead">{{ $event->summary }}</p>
                </div>

                <div class="event-facts">
                    <div>
                        <span class="fact-icon">▦</span>
                        <div>
                            <strong>{{ $event->start_time->locale('id')->translatedFormat('l, d F Y') }}</strong>
                            <span>
                                {{ $event->start_time->format('H:i') }} WIB
                                @if($event->end_time) – {{ $event->end_time->format('H:i') }} WIB @endif
                            </span>
                        </div>
                    </div>
                    <div>
                        <span class="fact-icon">⌖</span>
                        <div>
                            <strong>{{ $event->venue }}</strong>
                            <span>{{ $event->address }}, {{ $event->city }}</span>
                        </div>
                    </div>
                </div>

                <section class="description-block">
                    <h2>Tentang event</h2>
                    <div class="prose-content">{{ $event->description }}</div>
                </section>

                @if($event->images->isNotEmpty())
                    <section class="description-block">
                        <h2>Lebih dekat dengan momennya</h2>
                        <div class="gallery">
                            @foreach($event->images as $image)
                                <img src="{{ Storage::disk('public')->url($image->path) }}" alt="Galeri {{ $event->title }} {{ $loop->iteration }}" loading="lazy" width="800" height="600">
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="description-block">
                    <h2>Sebelum kamu berangkat</h2>
                    <p class="muted">Periksa kembali tanggal, lokasi, dan pilihan tiket. Tiket hanya berlaku untuk event dan kategori yang tercantum pada pesanan.</p>
                </section>
            </div>

            <aside class="booking-panel">
                <p class="eyebrow">YOUR WAY IN</p>
                <h2>Pilih tiketmu.</h2>
                <p class="muted">Pilih kategori, tentukan jumlah, siap berangkat.</p>

                @if($event->start_time->isPast())
                    <div class="notice">Event ini sudah berlangsung.</div>
                @else
                    <form action="{{ route('checkout', $event) }}" method="post" data-booking-form>
                        @csrf
                        <input type="hidden" name="checkout_key" value="{{ old('checkout_key', (string) Str::uuid()) }}">

                        @forelse($event->ticketCategories as $type)
                            <div class="ticket-option">
                                <div class="ticket-option-heading">
                                    <h3>{{ $type->name }}</h3>
                                    <span class="availability">{{ $type->stock > 0 ? 'Tersedia' : 'Habis' }}</span>
                                </div>
                                <p>{{ $type->description }}</p>
                                <div class="ticket-option-bottom">
                                    <strong>Rp{{ number_format($type->price, 0, ',', '.') }}</strong>
                                    <div class="stepper">
                                        <button type="button" data-step="-1" aria-label="Kurangi {{ $type->name }}">−</button>
                                        <input type="number" 
                                               name="tickets[{{ $type->id }}]" 
                                               min="0" 
                                               max="{{ min($type->stock, $type->max_per_order) }}" 
                                               value="{{ old('tickets.'.$type->id, 0) }}" 
                                               data-price="{{ $type->price }}" 
                                               aria-label="Jumlah {{ $type->name }}" 
                                               @disabled($type->stock === 0)>
                                        <button type="button" data-step="1" aria-label="Tambah {{ $type->name }}">+</button>
                                    </div>
                                </div>
                                <small>Maks. {{ $type->max_per_order }} tiket / pesanan</small>
                            </div>
                        @empty
                            <div class="notice">Pilihan tiket segera tersedia. Kunjungi kembali nanti.</div>
                        @endforelse

                        @if($event->ticketCategories->isNotEmpty())
                            <div class="booking-total">
                                <span>Total <small data-ticket-count>0 tiket</small></span>
                                <strong data-ticket-total>Rp0</strong>
                            </div>
                            <div class="booking-quota-alert" data-quota-alert role="alert"></div>
                            <p class="muted small-copy">Total final dihitung kembali saat membuat pesanan.</p>

                            @auth
                                <button class="button full" type="submit" data-booking-submit>Lanjut ke checkout <span>→</span></button>
                            @else
                                <a class="button full" href="{{ route('login') }}">Masuk untuk pesan <span>→</span></a>
                            @endauth
                            <p class="booking-note">Tiket disimpan selama 15 menit setelah checkout.</p>
                        @endif
                    </form>
                @endif
            </aside>
        </div>
    </div>
</x-layout>
