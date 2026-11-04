<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GateCheckInController extends Controller
{
    public function __construct(
        private BookingService $bookings,
    ) {}

    /**
     * Show the mobile/handheld gate scanner interface.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()?->canCheckInTickets(), 403);

        $events = Event::where('status', 'published')
            ->orderBy('start_time', 'desc')
            ->get();

        $selectedEventId = $request->integer('event_id') ?: ($events->first()?->id ?? null);

        return view('gate.scan', [
            'events' => $events,
            'selectedEventId' => $selectedEventId,
            'gateConfig' => $this->bookings->getGateConfig($selectedEventId),
        ]);
    }

    /**
     * Check-in a ticket in real-time.
     */
    public function checkIn(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canCheckInTickets(), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        $eventId = ! empty($validated['event_id']) ? (int) $validated['event_id'] : null;
        $result = $this->bookings->checkIn($validated['code'], $eventId);

        $ticket = $result['ticket'] ?? null;
        $orderItem = $ticket?->orderItem;
        $order = $orderItem?->order;

        return response()->json([
            'ok' => $result['ok'],
            'status' => $result['status'],
            'message' => $result['message'],
            'ticket' => $ticket ? [
                'code' => $ticket->code,
                'status' => $ticket->status,
                'checked_in_at' => $ticket->checked_in_at?->format('H:i:s') . ' WIB',
                'holder' => $order?->customer_name ?? $order?->user?->name ?? 'Pengunjung',
                'ticket_name' => $orderItem?->ticket_name ?? 'Tiket',
                'event_title' => $orderItem?->event_title ?? 'Event Konser',
                'venue' => $orderItem?->venue ?? 'Main Stage',
            ] : null,
        ], $result['ok'] ? 200 : 422);
    }

    /**
     * Update gate scanner settings (system on/off & start_time check-in window lock).
     */
    public function updateSettings(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'system_active' => ['required', 'boolean'],
            'time_lock_enabled' => ['required', 'boolean'],
            'hours_before_start' => ['required', 'integer', 'min:0', 'max:720'],
        ]);

        $eventId = ! empty($validated['event_id']) ? (int) $validated['event_id'] : null;
        $config = $this->bookings->setGateConfig(
            $eventId,
            (bool) $validated['system_active'],
            (bool) $validated['time_lock_enabled'],
            (int) $validated['hours_before_start']
        );

        $statusDesc = ! $config['system_active']
            ? 'Sistem Gate Dikunci (Scan Dimatikan)'
            : ($config['time_lock_enabled']
                ? "Kunci Waktu Aktif (Mulai H-{$config['hours_before_start']} jam sebelum acara)"
                : 'Bebas Scan Kapan Saja (Kunci Waktu Dimatikan)');

        return response()->json([
            'ok' => true,
            'gate_config' => $config,
            'message' => "Pengaturan gate disimpan: {$statusDesc}.",
        ]);
    }

    /**
     * Download the offline ticket manifest for a specific event.
     * Allows gate devices to scan with 0 latency even when internet/server is completely down.
     */
    public function manifest(Request $request, Event $event): JsonResponse
    {
        abort_unless($request->user()?->canCheckInTickets(), 403);

        $tickets = Ticket::whereHas('orderItem', function ($query) use ($event) {
            $query->whereHas('order', fn ($q) => $q->where('status', 'paid'))
                ->where(function ($q) use ($event) {
                    $q->whereHas('ticketCategory', fn ($tc) => $tc->where('event_id', $event->id))
                      ->orWhere('event_title', $event->title);
                });
        })->with(['orderItem.order.user'])->get();

        $payload = $tickets->map(function (Ticket $t) {
            $item = $t->orderItem;
            $order = $item?->order;

            return [
                'code' => $t->code,
                'signature' => $t->getHmacSignature(),
                'status' => $t->status,
                'checked_in_at' => $t->checked_in_at?->toIso8601String(),
                'holder' => $order?->customer_name ?? $order?->user?->name ?? 'Pengunjung',
                'ticket_name' => $item?->ticket_name ?? 'Tiket',
            ];
        });

        return response()->json([
            'event_id' => $event->id,
            'event_title' => $event->title,
            'event_start_time' => $event->start_time?->toIso8601String(),
            'event_start_formatted' => $event->start_time?->format('d M Y H:i') . ' WIB',
            'gate_config' => $this->bookings->getGateConfig($event->id),
            'total_tickets' => $tickets->count(),
            'synced_at' => now()->toIso8601String(),
            'tickets' => $payload,
        ]);
    }

    /**
     * 2-Way Multi-Device Synchronizer:
     * 1. Uploads offline check-in logs collected while a gate device was offline.
     * 2. Detects cross-device duplicate scan conflicts (if Device A and Device B scanned the same ticket offline).
     * 3. Returns latest `used_tickets` and `gate_config` so all staff devices stay in sync automatically.
     */
    public function syncBatch(Request $request): JsonResponse
    {
        abort_unless($request->user()?->canCheckInTickets(), 403);

        $validated = $request->validate([
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'check_ins' => ['present', 'array'],
            'check_ins.*.code' => ['required', 'string'],
            'check_ins.*.checked_in_at' => ['required', 'date'],
        ]);

        $synced = 0;
        $conflicts = [];

        DB::transaction(function () use ($validated, &$synced, &$conflicts) {
            foreach ($validated['check_ins'] as $entry) {
                if (! \Illuminate\Support\Str::isUuid($entry['code'])) {
                    continue;
                }

                /** @var Ticket|null $ticket */
                $ticket = Ticket::where('code', $entry['code'])
                    ->with(['orderItem.order.user'])
                    ->lockForUpdate()
                    ->first();

                if (! $ticket) {
                    continue;
                }

                if ($ticket->status !== 'used') {
                    $ticket->update([
                        'status' => 'used',
                        'checked_in_at' => $entry['checked_in_at'],
                    ]);
                    $synced++;
                } else {
                    // Conflict detected: Another staff device already checked in this ticket!
                    $order = $ticket->orderItem?->order;
                    $conflicts[] = [
                        'code' => $ticket->code,
                        'holder' => $order?->customer_name ?? $order?->user?->name ?? 'Pengunjung',
                        'previous_checked_in_at' => $ticket->checked_in_at?->format('H:i:s') . ' WIB',
                    ];
                }
            }
        });

        // Return latest used tickets for 2-way multi-device delta sync
        $eventId = ! empty($validated['event_id']) ? (int) $validated['event_id'] : null;
        $usedTickets = [];
        if ($eventId) {
            $usedTickets = Ticket::where('status', 'used')
                ->whereHas('orderItem.ticketCategory', fn ($q) => $q->where('event_id', $eventId))
                ->get(['code', 'checked_in_at'])
                ->map(fn (Ticket $t) => [
                    'code' => $t->code,
                    'checked_in_at' => $t->checked_in_at?->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        $conflictCount = count($conflicts);
        $msg = "Berhasil menyinkronkan {$synced} tiket dari data offline.";
        if ($conflictCount > 0) {
            $msg .= " ⚠️ Terdeteksi {$conflictCount} tiket di-scan ganda di perangkat panitia lain!";
        }

        return response()->json([
            'ok' => true,
            'synced_count' => $synced,
            'conflict_count' => $conflictCount,
            'conflicts' => $conflicts,
            'used_tickets' => $usedTickets,
            'gate_config' => $this->bookings->getGateConfig($eventId),
            'message' => $msg,
        ]);
    }
}
