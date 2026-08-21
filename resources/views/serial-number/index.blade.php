<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('ui.app_name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body data-environment="{{ app()->environment() }}">
    <main class="app-shell">
        @include('serial-number.partials.header', ['imageDirectory' => $imageDirectory])

        @include('serial-number.partials.tabs')

        <section id="recognition-tab" class="tab-content is-active">
            <section class="workspace">
                <aside class="image-panel">
                    <div class="panel-heading">
                        <h2>{{ __('ui.images') }}</h2>
                        <div class="panel-actions">
                            <span id="image-count">…</span>
                            <button
                                id="refresh-folder"
                                class="icon-button"
                                type="button"
                                title="{{ __('ui.refresh_image_directory') }}"
                                aria-label="{{ __('ui.refresh_image_directory') }}"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M20 11a8 8 0 1 0 2.1 5.4M20 4v7h-7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div id="image-catalog" aria-live="polite">
                        <p class="empty-state">{{ __('ui.loading_images') }}</p>
                    </div>
                    <nav
                        id="image-pagination"
                        class="image-pagination"
                        aria-label="{{ __('ui.images') }}"
                    ></nav>
                </aside>

                <section class="recognition-panel" aria-live="polite">
                    <div class="panel-heading">
                        <h2>{{ __('ui.results') }}</h2>
                        <span id="status">{{ __('ui.select_image') }}</span>
                    </div>

                    <p id="selected-image" class="selected-image">
                        {{ __('ui.select_image_hint') }}
                    </p>

                    <label class="result-label" for="local-result">
                        {{ __('ui.local_ocr') }}
                    </label>
                    <textarea
                        id="local-result"
                        class="result-field"
                        readonly
                        spellcheck="false"
                    >{{ __('ui.result_appears_here') }}</textarea>

                    <div class="ai-toolbar">
                        <label for="agent-select">{{ __('ui.ai_agent') }}</label>
                        <select id="agent-select" disabled>
                            <option value="">{{ __('ui.add_agent') }}</option>
                        </select>
                        <button id="recognize-ai" class="primary-button" type="button" disabled>
                            {{ __('ui.recognize_ai') }}
                        </button>
                    </div>

                    <label class="result-label" for="ai-result">
                        {{ __('ui.ai_recognition') }}
                    </label>
                    <textarea
                        id="ai-result"
                        class="result-field"
                        readonly
                        disabled
                        spellcheck="false"
                    >{{ __('ui.add_select_ai_agent') }}</textarea>
                </section>
            </section>
        </section>

        <section id="settings-tab" class="tab-content">
            <section class="settings-panel">
                <div class="settings-heading">
                    <div>
                        <h2>{{ __('ui.ai_agents') }}</h2>
                        <p>{{ __('ui.ai_agents_description') }}</p>
                    </div>
                    <button id="add-agent" class="primary-button" type="button">
                        {{ __('ui.add_agent') }}
                    </button>
                </div>

                <div id="agent-forms" class="agent-forms"></div>
            </section>
        </section>

        @include('serial-number.dialogs.about', ['appVersion' => $appVersion])
        @include('serial-number.dialogs.update')
        @include('serial-number.dialogs.confirmation')

        @include('serial-number.partials.footer', ['appVersion' => $appVersion])

    </main>

    <script>
        window.initialAiAgents = @json($agents);
        window.initialLocale = @json($locale);
        window.supportedLocales = @json($supportedLocales);
        window.i18n = @json($translations);
    </script>
    <script src="{{ asset('js/localization.js') }}?v={{ filemtime(public_path('js/localization.js')) }}"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>

    @if ($setupRequired)
        @include('serial-number.dialogs.first-run-setup', [
            'locale' => $locale,
            'imageDirectory' => $imageDirectory,
        ])
        <script src="{{ asset('js/first-run-setup.js') }}?v={{ filemtime(public_path('js/first-run-setup.js')) }}"></script>
    @endif
</body>
</html>
