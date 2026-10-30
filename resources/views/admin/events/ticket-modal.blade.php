<x-modal :id="$modalId" :title="$ticket->exists ? 'Edit pilihan tiket' : 'Tambah pilihan tiket'">
    <form action="{{ $ticket->exists ? route('admin.tickets.update',[$event,$ticket]) : route('admin.tickets.store',$event) }}" method="post" data-fetch-form data-close-on-success>@csrf @if($ticket->exists) @method('PUT') @endif
        <label class="field"><span>Nama pilihan tiket</span><input name="name" value="{{ $ticket->name }}" placeholder="VIP / CAT 1 / Regular" required maxlength="80"></label><label class="field"><span>Deskripsi singkat</span><textarea name="description" rows="2" maxlength="300">{{ $ticket->description }}</textarea></label>
        <div class="form-row"><label class="field"><span>Harga (rupiah)</span><input name="price" type="number" min="0" max="100000000" value="{{ $ticket->price ?? 0 }}" required></label><label class="field"><span>Total kuota</span><input name="capacity" type="number" min="{{ $ticket->exists ? $ticket->capacity-$ticket->stock : 0 }}" max="1000000" value="{{ $ticket->capacity ?? 100 }}" required></label></div>
        <div class="form-row"><label class="field"><span>Maksimal per pesanan</span><input name="max_per_order" type="number" min="1" max="20" value="{{ $ticket->max_per_order ?? 6 }}" required></label><label class="field"><span>Status penjualan</span><select name="active">
                    <option value="1" @selected(!$ticket->exists || $ticket->active)>Aktif</option>
                    <option value="0" @selected($ticket->exists && !$ticket->active)>Nonaktif</option>
                </select></label></div><button class="button full">Simpan tiket</button>
    </form>
</x-modal>