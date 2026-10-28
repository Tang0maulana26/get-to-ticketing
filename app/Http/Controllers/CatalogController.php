<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
        ]);

        $upcoming = Event::where('status', 'published')->where('start_time', '>', now());

        $events = (clone $upcoming)
            ->with('category')
            ->withMin(['ticketCategories' => fn ($query) => $query->where('active', true)], 'price')
            ->when($filters['q'] ?? null, function ($query, $search) {
                $pattern = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($q) => $q->where('title', 'ilike', $pattern)->orWhere('venue', 'ilike', $pattern));
            })
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereHas('category', fn ($q) => $q->where('slug', $slug)))
            ->when($filters['city'] ?? null, fn ($query, $city) => $query->where('city', $city))
            ->orderBy('start_time')
            ->paginate(6)
            ->withQueryString();

        return view('catalog.index', [
            'events' => $events,
            'featured' => (clone $upcoming)->where('featured', true)->orderBy('start_time')->first(),
            'categories' => Category::whereHas('events', fn ($q) => $q->where('status', 'published')->where('start_time', '>', now()))->get(),
            'cities' => (clone $upcoming)->distinct()->orderBy('city')->pluck('city'),
        ]);
    }

    public function show(Event $event)
    {
        abort_unless($event->status === 'published', 404);

        $event->load([
            'category',
            'images',
            'ticketCategories' => fn ($query) => $query->where('active', true)->orderBy('price'),
        ]);

        return view('catalog.show', compact('event'));
    }
}
