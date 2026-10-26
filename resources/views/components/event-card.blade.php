@props(['event'])
<article class="event-card">
    <a href="{{ route('events.show', $event->slug) }}" class="poster-link" tabindex="-1" aria-hidden="true"><img src="{{ $event->banner_url }}" alt="" loading="lazy" width="800" height="600"><span class="poster-label">{{ $event->category->name }}</span><span class="poster-arrow">↗</span></a>
    <div class="event-card-body"><p class="event-date">{{ $event->start_time->locale('id')->translatedFormat('d M Y') }} <span>•</span> {{ $event->city }}</p><h3><a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a></h3><p class="muted venue">{{ $event->venue }}</p><div class="event-card-bottom"><div><small>Mulai dari</small><strong>{{ $event->ticket_categories_min_price !== null ? 'Rp'.number_format($event->ticket_categories_min_price,0,',','.') : 'Segera tersedia' }}</strong></div><span class="tiny-label">Lihat tiket →</span></div></div>
</article>
