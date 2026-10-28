@props(['id','title'])
<dialog id="{{ $id }}" class="modal">
    <div class="modal-heading">
        <h2>{{ $title }}</h2><button type="button" class="icon-button" data-close-modal aria-label="Tutup">×</button>
    </div>{{ $slot }}
</dialog>