<header class="app-header">
    <div>
        <p class="eyebrow">{{ __('ui.tagline') }}</p>
        <h1>{{ __('ui.app_name') }}</h1>
    </div>

    <div class="directory">
        {{ __('ui.folder') }}
        <code>{{ $imageDirectory }}</code>
        <button id="open-image-directory" class="tab-button" type="button">
            {{ __('ui.open') }}
        </button>
    </div>
</header>
