<div x-data x-cloak>
    <div x-show="$store.alertModal.show" class="fixed inset-0 z-[95] bg-brand-navy/60" aria-hidden="true" @click="$store.alertModal.close()"></div>

    <div
        x-show="$store.alertModal.show"
        x-transition.opacity
        class="fixed inset-0 z-[96] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="alert-modal-title"
    >
        <div class="w-full max-w-md rounded-card border border-line bg-surface p-5 shadow-lg">
            <h2 id="alert-modal-title" class="text-lg font-extrabold text-brand-navy" x-text="$store.alertModal.title"></h2>
            <p class="mt-2 max-h-64 overflow-y-auto whitespace-pre-wrap break-words text-sm text-muted" x-text="$store.alertModal.message"></p>
            <div class="mt-4 flex justify-end">
                <x-ui.button type="button" @click="$store.alertModal.close()">Close</x-ui.button>
            </div>
        </div>
    </div>
</div>
