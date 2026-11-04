{{-- Modal form to add or edit a ticket category; submitted through fetch. --}}
<x-modal :id="$modalId" :title="$ticket->exists ? __('Edit ticket option') : __('Add ticket option')">
    <form action="{{ $ticket->exists ? route('admin.tickets.update',[$event,$ticket]) : route('admin.tickets.store',$event) }}" method="post" data-fetch-form data-close-on-success>@csrf @if($ticket->exists) @method('PUT') @endif
        <label class="field"><span>{{ __('Ticket option name') }}</span><input name="name" value="{{ $ticket->name }}" placeholder="VIP / CAT 1 / Regular" required maxlength="80"></label>
        <label class="field"><span>{{ __('Short description') }}</span><textarea name="description" rows="2" maxlength="300">{{ $ticket->description }}</textarea></label>
        <div class="form-row">
            <label class="field"><span>{{ __('Price (rupiah)') }}</span><input name="price" type="number" min="0" max="100000000" value="{{ $ticket->price ?? 0 }}" required></label>
            <label class="field"><span>{{ __('Total capacity') }}</span><input name="capacity" type="number" min="{{ $ticket->exists ? $ticket->capacity-$ticket->stock : 0 }}" max="1000000" value="{{ $ticket->capacity ?? 100 }}" required></label>
        </div>
        <div class="form-row">
            <label class="field"><span>{{ __('Max per order') }}</span><input name="max_per_order" type="number" min="1" max="20" value="{{ $ticket->max_per_order ?? 6 }}" required></label>
            <label class="field"><span>{{ __('Sale status') }}</span><select name="active">
                    <option value="1" @selected(!$ticket->exists || $ticket->active)>{{ __('Active') }}</option>
                    <option value="0" @selected($ticket->exists && !$ticket->active)>{{ __('Inactive') }}</option>
                </select></label>
        </div>
        <button class="button full">{{ __('Save ticket') }}</button>
    </form>
</x-modal>