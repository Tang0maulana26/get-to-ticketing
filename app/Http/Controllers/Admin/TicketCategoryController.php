<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TicketCategoryRequest;
use App\Models\Event;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TicketCategoryController extends Controller
{
    public function store(TicketCategoryRequest $request, Event $event)
    {
        Gate::authorize('update', $event);
        $event->ticketCategories()->create([...$request->validated(), 'stock' => $request->integer('capacity')]);

        return $this->done($request, 'Pilihan tiket ditambahkan.');
    }

    public function update(TicketCategoryRequest $request, Event $event, TicketCategory $ticketCategory)
    {
        $this->authorizeTicket($event, $ticketCategory);

        DB::transaction(function () use ($request, $ticketCategory) {
            $ticket = TicketCategory::whereKey($ticketCategory->id)->lockForUpdate()->firstOrFail();
            // Seats already sold or reserved can never be removed by shrinking capacity.
            $allocated = $ticket->capacity - $ticket->stock;
            $capacity = $request->integer('capacity');
            if ($capacity < $allocated) {
                throw ValidationException::withMessages(['capacity' => "Kuota minimal {$allocated}, karena sudah terjual atau sedang dipesan."]);
            }
            $ticket->update([...$request->validated(), 'stock' => $capacity - $allocated]);
        }, 3);

        return $this->done($request, 'Pilihan tiket diperbarui. Pesanan lama tidak berubah.');
    }

    public function destroy(Request $request, Event $event, TicketCategory $ticketCategory)
    {
        $this->authorizeTicket($event, $ticketCategory);

        DB::transaction(function () use ($ticketCategory) {
            $ticket = TicketCategory::whereKey($ticketCategory->id)->lockForUpdate()->firstOrFail();
            // Keep purchased categories for order history; just hide them from sale.
            $ticket->orderItems()->exists() ? $ticket->update(['active' => false]) : $ticket->delete();
        }, 3);

        return $this->done($request, 'Pilihan tiket dihapus atau dinonaktifkan jika sudah memiliki pesanan.');
    }

    /** JSON for Fetch API callers, flash + redirect for classic form posts. */
    private function done(Request $request, string $message)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('success', $message);
    }

    private function authorizeTicket(Event $event, TicketCategory $ticketCategory): void
    {
        Gate::authorize('update', $event);
        abort_unless($ticketCategory->event_id === $event->id, 404);
    }
}
