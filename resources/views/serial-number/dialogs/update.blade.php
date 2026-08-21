<dialog id="update-dialog" class="about-dialog" aria-labelledby="update-dialog-title">
    <section class="about-dialog-card">
        <p class="eyebrow">{{ __('ui.updates') }}</p>
        <h2 id="update-dialog-title">{{ __('ui.check_updates') }}</h2>
        <p id="update-dialog-message" class="update-dialog-message" aria-live="polite">
            {{ __('ui.checking_updates') }}
        </p>

        <div class="about-dialog-actions">
            <button id="update-close" class="primary-button" type="button">
                {{ __('ui.close') }}
            </button>
        </div>
    </section>
</dialog>
