<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketCategoryRequest;
use App\Models\{Event, TicketCategory};
use Illuminate\Support\Facades\{DB, Gate};
use Illuminate\Validation\ValidationException;

class TicketCategoryController extends Controller
{
    public function store(TicketCategoryRequest $request, Event $event)
    {
        Gate::authorize('update', $event);
        $event->ticketCategories()->create($request->validated() + ['stock' => $request->integer('capacity')]);
        return back()->with('success', 'Pilihan tiket ditambahkan.');
    }
    public function update(TicketCategoryRequest $request, Event $event, TicketCategory $ticketCategory)
    {
        Gate::authorize('update', $event); abort_unless($ticketCategory->event_id === $event->id, 404);
        DB::transaction(function () use ($request, $ticketCategory) {
            $ticket = TicketCategory::whereKey($ticketCategory->id)->lockForUpdate()->firstOrFail();
            $allocated = $ticket->capacity - $ticket->stock;
            if ($request->integer('capacity') < $allocated) { throw ValidationException::withMessages(['capacity' => "Kuota minimal {$allocated}, karena sudah terjual atau sedang dipesan."]); }
            $ticket->update($request->validated() + ['stock' => $request->integer('capacity') - $allocated]);
        }, 3);
        return back()->with('success', 'Pilihan tiket diperbarui. Pesanan lama tidak berubah.');
    }
    public function destroy(Event $event, TicketCategory $ticketCategory)
    {
        Gate::authorize('update', $event); abort_unless($ticketCategory->event_id === $event->id, 404);
        DB::transaction(function () use ($ticketCategory) {
            $ticket = TicketCategory::whereKey($ticketCategory->id)->lockForUpdate()->firstOrFail();
            $ticket->orderItems()->exists() ? $ticket->update(['active' => false]) : $ticket->delete();
        }, 3);
        return back()->with('success', 'Pilihan tiket dihapus atau dinonaktifkan jika sudah memiliki pesanan.');
    }
}
