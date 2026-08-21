<section
    id="first-run-setup"
    class="first-run-setup"
    role="dialog"
    aria-modal="true"
    aria-labelledby="setup-title"
>
    <div class="first-run-card">
        <p class="eyebrow">{{ __('ui.first_run') }}</p>
        <h2 id="setup-title">{{ __('ui.setup_title') }}</h2>
        <p>{{ __('ui.setup_intro') }}</p>

        <div class="license-placeholder">
            <strong>{{ __('ui.agreement') }}</strong>
            <p>{{ __('ui.agreement_text') }}</p>
        </div>

        <label class="setup-agreement">
            <input id="setup-accepted" type="checkbox">
            {{ __('ui.accept_agreement') }}
        </label>

        <label>
            {{ __('ui.language') }}
            <select id="setup-locale">
                <option value="uk" @selected($locale === 'uk')>{{ __('ui.ukrainian') }}</option>
                <option value="en" @selected($locale === 'en')>{{ __('ui.english') }}</option>
                <option value="pl" @selected($locale === 'pl')>{{ __('ui.polish') }}</option>
            </select>
        </label>

        <label>
            {{ __('ui.image_directory') }}
            <span class="setup-directory">
                <input
                    id="setup-image-directory"
                    type="text"
                    readonly
                    value="{{ $imageDirectory }}"
                    placeholder="{{ __('ui.select_folder') }}"
                >
                <button id="choose-setup-directory" class="tab-button" type="button">
                    {{ __('ui.choose') }}
                </button>
            </span>
        </label>

        <p id="setup-message" class="agent-message"></p>

        <div class="about-dialog-actions">
            <button id="skip-setup" class="tab-button" type="button">
                {{ __('ui.configure_later') }}
            </button>
            <button
                id="complete-setup"
                class="primary-button"
                type="button"
                disabled
                aria-disabled="true"
            >
                {{ __('ui.complete_setup') }}
            </button>
        </div>
    </div>
</section>
