<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\{Category, Event};
use App\Services\EventMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Gate, Storage};

class EventController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Event::class);
        $query = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        return view('admin.events.index', ['events' => Event::with('category')->withCount('ticketCategories')->when($query['q'] ?? null, fn ($b, $q) => $b->where('title', 'ilike', '%'.$q.'%'))->latest()->paginate(12)->withQueryString()]);
    }
    public function create()
    {
        Gate::authorize('create', Event::class);
        return view('admin.events.form', ['event' => new Event, 'categories' => Category::all()]);
    }
    public function store(EventRequest $request, EventMedia $media)
    {
        $event = DB::transaction(function () use ($request, $media) {
            $event = Event::create($this->data($request)); $this->media($request, $event, $media); return $event;
        });
        return to_route('admin.events.edit', $event)->with('success', 'Event dibuat. Tambahkan pilihan tiket di bawah.');
    }
    public function show(Event $event) { return to_route('admin.events.edit', $event); }
    public function edit(Event $event)
    {
        Gate::authorize('update', $event); $event->load(['ticketCategories', 'images']);
        return view('admin.events.form', ['event' => $event, 'categories' => Category::all()]);
    }
    public function update(EventRequest $request, Event $event, EventMedia $media)
    {
        Gate::authorize('update', $event);
        DB::transaction(function () use ($request, $event, $media) { $event->update($this->data($request)); $this->media($request, $event, $media); });
        return back()->with('success', 'Perubahan event disimpan.');
    }
    public function destroy(Event $event)
    {
        Gate::authorize('delete', $event); $event->delete();
        return to_route('admin.events.index')->with('success', 'Event dihapus dari katalog. Riwayat pesanan tetap tersimpan.');
    }
    private function data(EventRequest $request): array
    {
        return array_merge($request->safe()->except(['banner_file', 'gallery', 'remove_images', 'image_positions']), ['featured' => $request->boolean('featured')]);
    }
    private function media(EventRequest $request, Event $event, EventMedia $media): void
    {
        if ($request->hasFile('banner_file')) { $event->update(['banner' => $media->store($request->file('banner_file'))]); }
        foreach ($event->images()->whereIn('id', $request->input('remove_images', []))->get() as $image) {
            $image->delete(); DB::afterCommit(fn () => Storage::disk('public')->delete($image->path));
        }
        foreach ($request->input('image_positions', []) as $id => $position) { $event->images()->whereKey($id)->update(['position' => $position]); }
        foreach ($request->file('gallery', []) as $position => $file) { $event->images()->create(['path' => $media->store($file), 'position' => $position]); }
    }
}
