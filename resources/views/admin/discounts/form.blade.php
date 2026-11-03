{{-- Admin discount create/edit form. --}}
<x-layout :title="$isEdit ? 'Edit Kode Diskon' : 'Buat Kode Diskon'" :admin="true">
    <section class="shell page-space">
        <a class="back-link" href="{{ route('admin.discounts.index') }}">← Kelola Diskon</a>

        <div class="section-heading">
            <div>
                <p class="eyebrow">DISCOUNT BUILDER</p>
                <h1>{{ $isEdit ? 'Perbarui diskon.' : 'Buat kode diskon baru.' }}</h1>
                <p class="muted">Tentukan aturan potongan, batas kuota, dan masa berlaku promo.</p>
            </div>
        </div>

        <form class="admin-form" action="{{ $isEdit ? route('admin.discounts.update', $discount) : route('admin.discounts.store') }}" method="post">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="panel">
                <h2>Ketentuan Diskon</h2>
                
                <div class="form-row">
                    <x-field label="Kode Diskon (Huruf kapital & angka)" 
                             name="code" 
                             :value="$discount->code" 
                             placeholder="CONTOH: HEMAT20" 
                             required 
                             maxlength="50" 
                             style="text-transform: uppercase;" />
                             
                    <x-field label="Nama Promo / Keterangan" 
                             name="name" 
                             :value="$discount->name" 
                             placeholder="Promo Spesial Peluncuran" 
                             required 
                             maxlength="120" />
                </div>

                <label class="field">
                    <span>Cakupan Event (Opsional)</span>
                    <select name="event_id">
                        <option value="">Semua Event (Berlaku Global)</option>
                        @foreach($events as $ev)
                            <option value="{{ $ev->id }}" @selected(old('event_id', $discount->event_id) == $ev->id)>
                                {{ $ev->title }} ({{ $ev->city }} - {{ $ev->start_time->format('d M Y') }})
                            </option>
                        @endforeach
                    </select>
                    <small class="muted">Pilih "Semua Event" jika diskon dapat digunakan pada konser apa saja, atau pilih satu konser tertentu agar promo hanya berlaku untuk event tersebut.</small>
                </label>

                <div class="form-row">
                    <label class="field">
                        <span>Tipe Potongan</span>
                        <select name="type" id="discount-type">
                            <option value="percent" @selected(old('type', $discount->type) === 'percent')>Persentase (%)</option>
                            <option value="fixed" @selected(old('type', $discount->type) === 'fixed')>Nominal Tetap (Rp)</option>
                        </select>
                    </label>

                    <x-field label="Nilai Potongan (angka saja)" 
                             name="value" 
                             type="number" 
                             :value="$discount->value" 
                             required 
                             min="1" />
                </div>

                <div class="form-row">
                    <x-field label="Minimal Pembelian (Rp, 0 jika tanpa minimal)" 
                             name="min_order_amount" 
                             type="number" 
                             :value="$discount->min_order_amount ?? 0" 
                             min="0" />

                    <x-field label="Maksimal Potongan (Rp, opsional jika persen)" 
                             name="max_discount_amount" 
                             type="number" 
                             :value="$discount->max_discount_amount" 
                             placeholder="Kosongkan jika tanpa batas" 
                             min="0" />
                </div>
            </div>

            <div class="panel">
                <h2>Kuota & Jadwal</h2>

                <x-field label="Batas Kuota Pemakaian (kosongkan jika tanpa batas)" 
                         name="usage_limit" 
                         type="number" 
                         :value="$discount->usage_limit" 
                         placeholder="Contoh: 100" 
                         min="1" />

                <div class="form-row">
                    <x-field label="Mulai Berlaku (opsional)" 
                             name="start_time" 
                             type="datetime-local" 
                             :value="$discount->start_time?->format('Y-m-d\TH:i')" />

                    <x-field label="Berakhir / Kedaluwarsa (opsional)" 
                             name="end_time" 
                             type="datetime-local" 
                             :value="$discount->end_time?->format('Y-m-d\TH:i')" />
                </div>

                <label class="check-field mt-4">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $discount->active ?? true))>
                    Aktifkan kode diskon ini sekarang
                </label>

                <div class="mt-6">
                    <button class="button full" type="submit">
                        {{ $isEdit ? 'Simpan Perubahan' : 'Buat Kode Diskon' }} →
                    </button>
                </div>
            </div>
        </form>
    </section>
</x-layout>

