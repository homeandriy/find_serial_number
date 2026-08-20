<!DOCTYPE html>
<html lang="uk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Обладнання та дані</title><link rel="icon" href="{{ asset('favicon.ico') }}"><link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}"></head>
<body data-environment="{{ app()->environment() }}"><main class="app-shell"><header class="app-header"><div><p class="eyebrow">Локальне та AI-розпізнавання</p><h1>Обладнання та дані</h1></div><div class="directory">Папка: <code>{{ $imageDirectory }}</code><button id="open-image-directory" class="tab-button" type="button">Відкрити</button></div></header>
<nav class="tabs" aria-label="Навігація"><button class="tab-button is-active" type="button" data-tab="recognition">Розпізнавання</button><button class="tab-button" type="button" data-tab="settings">Налаштування</button></nav>
<section id="recognition-tab" class="tab-content is-active"><section class="workspace"><aside class="image-panel"><div class="panel-heading"><h2>Зображення</h2><div class="panel-actions"><span>{{ count($images) }}</span><button id="refresh-folder" class="icon-button" type="button" title="Оновити папку зображень" aria-label="Оновити папку зображень"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0 2.1 5.4M20 4v7h-7"/></svg></button></div></div>
@if ($images === [])<p class="empty-state">У папці немає зображень. Додайте JPG, PNG, WEBP, TIFF або BMP до вказаної папки.</p>
@else @foreach (collect($images)->groupBy('uploaded_on') as $dayImages)<section class="image-day"><div class="image-day-divider"><span>{{ $dayImages->first()['uploaded_label'] }}</span></div><div class="image-grid">@foreach ($dayImages as $image)<article class="image-card" data-image-id="{{ $image['id'] }}" data-image-name="{{ $image['name'] }}"><button class="image-preview" type="button"><img src="{{ url('/images/'.$image['id']) }}" alt="{{ $image['name'] }}" loading="lazy"></button><div class="image-card-footer"><span title="{{ $image['name'] }}">{{ $image['name'] }}</span><button class="image-card-menu" type="button" aria-label="Меню фото">⋮</button></div></article>@endforeach</div></section>@endforeach @endif
</aside><section class="recognition-panel" aria-live="polite"><div class="panel-heading"><h2>Результати</h2><span id="status">Виберіть зображення</span></div><p id="selected-image" class="selected-image">Текст буде розпізнано після натискання на зображення зліва.</p><label class="result-label" for="local-result">Локальний OCR</label><textarea id="local-result" class="result-field" readonly spellcheck="false">Результат з’явиться тут.</textarea><div class="ai-toolbar"><label for="agent-select">AI-агент</label><select id="agent-select" disabled><option value="">Спочатку додайте агента</option></select><button id="recognize-ai" class="primary-button" type="button" disabled>Розпізнати AI</button></div><label class="result-label" for="ai-result">AI-розпізнавання</label><textarea id="ai-result" class="result-field" readonly disabled spellcheck="false">Додайте та виберіть AI-агента у налаштуваннях.</textarea></section></section></section>
<section id="settings-tab" class="tab-content"><section class="settings-panel"><div class="settings-heading"><div><h2>AI-агенти</h2><p>Додавайте скільки завгодно постачальників. API-токен шифрується локально й не показується після збереження.</p></div><button id="add-agent" class="primary-button" type="button">Додати агента</button></div><div id="agent-forms" class="agent-forms"></div></section></section>
<dialog id="about-dialog" class="about-dialog" aria-labelledby="about-dialog-title">
  <section class="about-dialog-card">
    <img src="{{ asset('icon.png') }}" alt="Serial Vision" class="about-dialog-logo">
    <p class="eyebrow">Про програму</p>
    <h2 id="about-dialog-title">Обладнання та дані</h2>
    <p>Локальний застосунок для обліку обладнання та розпізнавання серійних номерів.</p>
    <dl><div><dt>Версія</dt><dd>v{{ $appVersion }}</dd></div><div><dt>Розробник</dt><dd>homeandriy</dd></div><div><dt>Сайт</dt><dd><a id="about-external-homeandriy" href="https://webbooks.com.ua">webbooks.com.ua</a></dd></div></dl>
    <div class="about-dialog-actions"><button id="about-open-site" class="tab-button" type="button">Відкрити сайт</button><button id="about-close" class="primary-button" type="button">Закрити</button></div>
  </section>
</dialog><footer class="app-version"><span id="active-tab-name">Розпізнавання</span> | Обладнання та дані · by <a id="external-homeandriy" href="https://webbooks.com.ua">homeandriy</a> · v{{ $appVersion }}</footer></main><script>window.initialAiAgents=@json($agents);</script><script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>@if ($setupRequired)
<section id="first-run-setup" class="first-run-setup" role="dialog" aria-modal="true" aria-labelledby="setup-title">
  <div class="first-run-card">
    <p class="eyebrow">Перший запуск</p>
    <h2 id="setup-title">Налаштування Serial Vision</h2>
    <p>Оберіть папку з фотографіями. Її завжди можна змінити у налаштуваннях програми.</p>
    <div class="license-placeholder"><strong>Ліцензійна угода (заглушка)</strong><p>Програма надається «як є» для локальної обробки обладнання та зображень. Користувач відповідає за законність обробки даних і резервні копії.</p></div>
    <label class="setup-agreement"><input id="setup-accepted" type="checkbox"> Я прочитав(-ла) та погоджуюся з умовами.</label>
    <label>Папка з зображеннями<div class="setup-directory"><input id="setup-image-directory" type="text" readonly value="{{ $imageDirectory }}" placeholder="Оберіть папку"><button id="choose-setup-directory" class="tab-button" type="button">Вибрати</button></div></label>
    <p id="setup-message" class="agent-message"></p>
    <button id="complete-setup" class="primary-button" type="button" disabled aria-disabled="true">Завершити та відкрити програму</button>
  </div>
</section>
<script>
(() => {
    const setup = document.querySelector('#first-run-setup');
    if (!setup) return;

    const accepted = setup.querySelector('#setup-accepted');
    const directory = setup.querySelector('#setup-image-directory');
    const choose = setup.querySelector('#choose-setup-directory');
    const complete = setup.querySelector('#complete-setup');
    const message = setup.querySelector('#setup-message');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const isDevelopment = @json(app()->environment('local'));
    const sync = () => { const canComplete = accepted.checked && directory.value.trim().length > 0; complete.disabled = !canComplete; complete.setAttribute('aria-disabled', String(!canComplete)); };
    const post = async (url, data = {}) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(data),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || 'Операцію не вдалося виконати.');
        return payload;
    };

    accepted.addEventListener('change', sync, true);
    choose.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();
        choose.disabled = true;
        message.textContent = 'Відкриваємо вибір папки…';
        try {
            const result = await post('/image-directory/choose');
            if (result.path) directory.value = result.path;
            message.textContent = result.path ? 'Папку вибрано.' : 'Вибір скасовано.';
        } catch (error) {
            message.textContent = error.message;
        } finally {
            choose.disabled = false;
            sync();
        }
    }, true);
    complete.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();
        complete.disabled = true;
        message.textContent = 'Зберігаємо налаштування…';
        try {
            await post('/setup', { accepted: accepted.checked, path: directory.value });
            if (isDevelopment) {
                setup.remove();
            } else {
                window.location.reload();
            }
        } catch (error) {
            message.textContent = error.message;
            sync();
        }
    }, true);
    sync();
})();
</script>
@endif
</body></html>
