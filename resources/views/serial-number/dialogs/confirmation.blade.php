<dialog
    id="confirmation-dialog"
    class="about-dialog confirmation-dialog"
    aria-labelledby="confirmation-dialog-title"
>
    <section class="about-dialog-card">
        <p class="eyebrow">{{ __('ui.confirm_action') }}</p>
        <h2 id="confirmation-dialog-title">{{ __('ui.confirm_action') }}</h2>
        <p id="confirmation-dialog-message"></p>

        <div class="about-dialog-actions">
            <button id="confirmation-cancel" class="tab-button" type="button">
                {{ __('ui.confirm_cancel') }}
            </button>
            <button id="confirmation-accept" class="danger-button" type="button">
                {{ __('ui.delete') }}
            </button>
        </div>
    </section>
</dialog>
