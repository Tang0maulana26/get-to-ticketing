<?php
namespace App\Http\Controllers;

use App\Models\{Category, Event};
use App\Services\BookingService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:80'], 'city' => ['nullable', 'string', 'max:80']]);
        $base = Event::where('status', 'published')->where('start_time', '>', now());
        $events = (clone $base)->with('category')->withMin(['ticketCategories' => fn ($q) => $q->where('active', true)], 'price')
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($i) => $i->where('title', 'ilike', '%'.$v.'%')->orWhere('venue', 'ilike', '%'.$v.'%')))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->whereHas('category', fn ($c) => $c->where('slug', $v)))
            ->when($filters['city'] ?? null, fn ($q, $v) => $q->where('city', $v))
            ->orderBy('start_time')->paginate(6)->withQueryString();
        return view('catalog.index', ['events' => $events, 'featured' => (clone $base)->where('featured', true)->orderBy('start_time')->first(), 'categories' => Category::whereHas('events', fn ($q) => $q->where('status', 'published')->where('start_time', '>', now()))->get(), 'cities' => (clone $base)->select('city')->distinct()->orderBy('city')->pluck('city')]);
    }
    public function show(Event $event, BookingService $bookings)
    {
        abort_unless($event->status === 'published', 404);
        $bookings->expire();
        $event->load(['category', 'images', 'ticketCategories' => fn ($q) => $q->where('active', true)->orderBy('price')]);
        return view('catalog.show', compact('event'));
    }
}
