{{-- Admin create/edit event form with media management and its ticket category list. --}}
<x-layout :title="$event->exists ? __('Edit event') : __('Create event')" :admin="true">
    <section class="shell page-space"><a class="back-link" href="{{ route('admin.events.index') }}">← {{ __('Events & tickets') }}</a>
        <div class="section-heading">
            <div>
                <h1>{{ $event->exists ? __('Manage your event.') : __('Start a new story.') }}</h1>
                <p class="muted">{{ __('Fill in event details, then manage ticket options and quotas.') }}</p>
            </div>@if($event->exists && $event->status === 'published')<a class="button secondary" href="{{ route('events.show', $event->slug) }}">{{ __('View event page ↗') }}</a>@endif
        </div>
        <form class="admin-form" action="{{ $event->exists ? route('admin.events.update',$event) : route('admin.events.store') }}" method="post" enctype="multipart/form-data">@csrf @if($event->exists) @method('PUT') @endif
            <div class="panel">
                <h2>{{ __('Event information') }}</h2>
                <x-field :label="__('Event title')" name="title" :value="$event->title" required maxlength="160" />
                <x-field :label="__('Page URL / slug (letters, numbers, dashes)')" name="slug" :value="$event->slug" placeholder="concert-name-2026" required maxlength="180" />
                <label class="field"><span>{{ __('Short summary') }}</span><textarea name="summary" rows="3" required maxlength="300">{{ old('summary',$event->summary) }}</textarea><small>{{ __('Displayed below event title. Max 300 characters.') }}</small></label>
                <label class="field"><span>{{ __('Full description') }}</span><textarea name="description" rows="7" required maxlength="20000">{{ old('description',$event->description) }}</textarea><small>{{ __('Write performer info, experience, and terms in plain text.') }}</small></label>
                <div class="form-row">
                    <x-field :label="__('Venue name')" name="venue" :value="$event->venue" required maxlength="160" />
                    <x-field :label="__('City')" name="city" :value="$event->city" required maxlength="80" />
                </div>
                <x-field :label="__('Full address')" name="address" :value="$event->address" required maxlength="300" />
                <div class="form-row">
                    <x-field :label="__('Start time (WIB)')" name="start_time" type="datetime-local" :value="$event->start_time?->format('Y-m-d\TH:i')" required />
                    <x-field :label="__('End time (WIB, optional)')" name="end_time" type="datetime-local" :value="$event->end_time?->format('Y-m-d\TH:i')" />
                </div>
            </div>
            <div class="panel">
                <h2>{{ __('Display & publication') }}</h2>
                <label class="field"><span>{{ __('Event category') }}</span><select name="category_id">@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id',$event->category_id)==$category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label class="field"><span>{{ __('Status') }}</span><select name="status">@foreach(['draft'=>__('Draft'),'published'=>__('Published'),'archived'=>__('Archived')] as $value=>$label)<option value="{{ $value }}" @selected(old('status',$event->status ?? 'draft')===$value)>{{ $label }}</option>@endforeach</select></label>
                <label class="check-field"><input type="checkbox" name="featured" value="1" @checked(old('featured',$event->featured))>{{ __('Feature this event') }}</label>
                @if($event->exists)<img src="{{ $event->banner_url }}" alt="Banner {{ $event->title }}" class="rounded-lg mb-4" width="800" height="600">@endif
                <label class="field"><span>{{ __('Main banner') }}</span><input type="file" name="banner_file" accept="image/jpeg,image/png,image/webp"><small>{{ __('JPG, PNG, WebP. Max 4 MB. Recommended ratio 3:2.') }}</small></label>
                <label class="field"><span>{{ __('Add gallery images (optional)') }}</span><input type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp"><small>{{ __('Max 6 images per upload, 4 MB each.') }}</small></label>
                @if($event->exists && $event->images->isNotEmpty())
                <div class="admin-gallery">@foreach($event->images as $image)<div><img src="{{ Storage::disk('public')->url($image->path) }}" alt="Galeri {{ $loop->iteration }}"><label class="field"><span>{{ __('Order position') }}</span><input name="image_positions[{{ $image->id }}]" type="number" value="{{ $image->position }}" min="0" max="99"></label><label class="check-field"><input type="checkbox" name="remove_images[]" value="{{ $image->id }}">{{ __('Delete image') }}</label></div>@endforeach</div>
                @endif
                <button class="button full">{{ $event->exists ? __('Save changes') : __('Create event & continue to tickets') }} →</button>
            </div>
        </form>
        @if($event->exists)<div id="ticket-section" data-refresh-target>
        <div class="section-heading mt-10">
            <div>
                <h2>{{ __('Ticket options') }}</h2>
                <p class="muted">{{ __('Name, price, and quotas can be adjusted. Previous orders retain their purchase data.') }}</p>
            </div><button class="button" type="button" data-open-modal="ticket-new">+ {{ __('Add ticket') }}</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Price') }}</th>
                        <th>{{ __('Capacity') }}</th>
                        <th>{{ __('Available') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>@forelse($event->ticketCategories as $ticket)<tr>
                        <td><strong>{{ $ticket->name }}</strong><small>{{ $ticket->description }}</small></td>
                        <td>Rp{{ number_format($ticket->price,0,',','.') }}</td>
                        <td>{{ $ticket->capacity }}</td>
                        <td>{{ $ticket->stock }}</td>
                        <td>{{ $ticket->active ? __('Active') : __('Inactive') }}</td>
                        <td>
                            <div class="table-actions"><button data-open-modal="ticket-{{ $ticket->id }}" type="button">{{ __('Edit') }}</button>
                                <form action="{{ route('admin.tickets.destroy',[$event,$ticket]) }}" method="post" data-fetch-form data-confirm="{{ __('Delete ticket option? If already purchased, it will be deactivated instead.') }}">@csrf @method('DELETE')<button>{{ __('Delete') }}</button></form>
                            </div>
                        </td>
                    </tr>@empty<tr>
                        <td colspan="6">{{ __('Add ticket options like VIP, CAT 1, or Regular.') }}</td>
                    </tr>@endforelse</tbody>
            </table>
        </div>
        @include('admin.events.ticket-modal',['ticket'=>new \App\Models\TicketCategory,'modalId'=>'ticket-new'])
        @foreach($event->ticketCategories as $ticket)@include('admin.events.ticket-modal',['ticket'=>$ticket,'modalId'=>'ticket-'.$ticket->id])@endforeach
        </div>
        @endif
    </section>
</x-layout>