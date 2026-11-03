{{-- Reusable <dialog> modal shell; opened and closed by data attributes handled in app.js. --}}
@props(['id','title'])
<dialog id="{{ $id }}" class="modal">
    <div class="modal-heading">
        <h2>{{ $title }}</h2><button type="button" class="icon-button" data-close-modal aria-label="Tutup">×</button>
    </div>{{ $slot }}
</dialog>