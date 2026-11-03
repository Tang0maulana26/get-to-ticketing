{{-- Admin discount management list. --}}
<x-layout title="Kelola Diskon" :admin="true">
    <section class="shell page-space">
        <div class="section-heading">
            <div>
                <p class="eyebrow">PROMO & DISCOUNTS</p>
                <h1>Kode Diskon.</h1>
                <p class="muted">Atur kode promo, persentase potongan, kuota, dan masa berlaku.</p>
            </div>
            <a class="button" href="{{ route('admin.discounts.create') }}">+ Buat Diskon</a>
        </div>

        <form class="filter-bar" method="get">
            <label class="search-field">
                <span>⌕</span>
                <input name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama promo..." aria-label="Cari diskon" maxlength="100">
            </label>
            <select name="status" aria-label="Status diskon">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            <button class="button secondary" type="submit">Filter</button>
            @if(request()->hasAny(['q', 'status']))
                <a class="text-link" href="{{ route('admin.discounts.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Promo</th>
                        <th>Nilai Potongan</th>
                        <th>Min. Pembelian</th>
                        <th>Kuota / Terpakai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discounts as $discount)
                        <tr>
                            <td>
                                <strong><code>{{ $discount->code }}</code></strong>
                            </td>
                            <td>
                                <strong>{{ $discount->name }}</strong>
                                @if($discount->event)
                                    <small style="color: #14446b; display: block;">🎯 Khusus: {{ $discount->event->title }}</small>
                                @else
                                    <small class="muted" style="display: block;">Semua Event</small>
                                @endif
                            </td>
                            <td>
                                @if($discount->type === 'percent')
                                    <strong>{{ $discount->value }}%</strong>
                                    @if($discount->max_discount_amount)
                                        <small>(Maks. Rp{{ number_format($discount->max_discount_amount, 0, ',', '.') }})</small>
                                    @endif
                                @else
                                    <strong>Rp{{ number_format($discount->value, 0, ',', '.') }}</strong>
                                @endif
                            </td>
                            <td>
                                {{ $discount->min_order_amount > 0 ? 'Rp' . number_format($discount->min_order_amount, 0, ',', '.') : 'Tanpa min.' }}
                            </td>
                            <td>
                                {{ $discount->used_count }} / {{ $discount->usage_limit ?? '∞' }}
                            </td>
                            <td>
                                @if($discount->active)
                                    <span class="badge badge-published">Aktif</span>
                                @else
                                    <span class="badge badge-draft">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="text-link" href="{{ route('admin.discounts.edit', $discount) }}">Edit</a>
                                    <form action="{{ route('admin.discounts.destroy', $discount) }}" method="post" data-confirm="Hapus atau nonaktifkan kode diskon ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">Belum ada kode diskon. Buat kode promo pertamamu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $discounts->links() }}</div>
    </section>
</x-layout>

