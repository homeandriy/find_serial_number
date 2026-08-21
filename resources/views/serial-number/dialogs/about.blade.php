<dialog id="about-dialog" class="about-dialog" aria-labelledby="about-dialog-title">
    <section class="about-dialog-card">
        <img src="{{ asset('icon.png') }}" alt="Serial Vision" class="about-dialog-logo">

        <p class="eyebrow">{{ __('ui.about') }}</p>
        <h2 id="about-dialog-title">{{ __('ui.app_name') }}</h2>
        <p>{{ __('ui.about_description') }}</p>

        <dl>
            <div>
                <dt>{{ __('ui.version') }}</dt>
                <dd>v{{ $appVersion }}</dd>
            </div>
            <div>
                <dt>{{ __('ui.developer') }}</dt>
                <dd>homeandriy</dd>
            </div>
            <div>
                <dt>{{ __('ui.website') }}</dt>
                <dd>
                    <a id="about-external-homeandriy" href="https://webbooks.com.ua">
                        webbooks.com.ua
                    </a>
                </dd>
            </div>
        </dl>

        <div class="about-dialog-actions">
            <button id="about-open-site" class="tab-button" type="button">
                {{ __('ui.open_website') }}
            </button>
            <button id="about-close" class="primary-button" type="button">
                {{ __('ui.close') }}
            </button>
        </div>
    </section>
</dialog>
