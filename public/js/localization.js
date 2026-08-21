(() => {
    const sourceTexts = {
        "text.choose.an.image": "Виберіть зображення",
        "text.local.ocr": "Локальний OCR",
        "text.the.result.will.appear.here": "Результат з’явиться тут.",
        "text.folder": "Папка:",
        "text.serial.numbers.mac.text": "Серійники / MAC / текст",
        "text.contract.number": "Номер договору",
        "text.for.example.123.45": "Наприклад, 123/45",
        "text.operation": "Операція",
        "text.add.record.to.database": "Додати запис у БД",
        "text.export.to.excel": "Вигрузити в Excel",
        "text.number.of.requests.receipts.and.issues.from.saved.equipment.records":
            "Кількість заявок, прийомів і видач за внесеними записами обладнання.",
        "text.statistics.for.the.current.month.from.the.1st.day":
            "Статистика за поточний місяць — від 1-го числа.",
        "text.number.of.operations.by.day": "Кількість операцій по днях.",
        "text.number.of.operations.by.month": "Кількість операцій по місяцях.",
        "text.internet.and.television": "Інтернет і телебачення.",
        "text.number.of.requests.by.equipment.name":
            "Кількість заявок за назвою обладнання.",
        "text.date.and.time": "Дата і час",
        "text.update.checking.is.available.in.the.installed.version.of.the.application":
            "Перевірка оновлень доступна у встановленій версії програми.",
        "text.local.application.for.equipment.tracking.and.serial.number.recognition":
            "Локальний застосунок для обліку обладнання та розпізнавання серійних номерів.",
        "text.version": "Версія",
        "text.developer": "Розробник",
        "text.website": "Сайт",
        "text.open.website": "Відкрити сайт",
        "text.equipment.and.data": "Обладнання та дані",
        "text.local.and.ai.recognition": "Локальне та AI-розпізнавання",
        "text.recognition": "Розпізнавання",
        "text.equipment": "Обладнання",
        "text.models": "Моделі",
        "text.statistics": "Статистика",
        "text.settings": "Налаштування",
        "text.images": "Зображення",
        "text.results": "Результати",
        "text.open": "Відкрити",
        "text.choose": "Вибрати",
        "text.choose.folder": "Вибрати папку",
        "text.save": "Зберегти",
        "text.cancel": "Скасувати",
        "text.close": "Закрити",
        "text.delete": "Видалити",
        "text.edit": "Редагувати",
        "text.add.to.database": "Додати в БД",
        "text.add.unformatted.to.database": "Додати в БД неформатовано",
        "text.add.model": "Додати модель",
        "text.model.directory": "Довідник моделей",
        "text.all.types": "Всі типи",
        "text.all.services": "Всі послуги",
        "text.modem": "Модем",
        "text.set.top.box": "Тюнер",
        "text.internet": "Інтернет",
        "text.television": "Телебачення",
        "text.name": "Назва",
        "text.model": "Модель",
        "text.type": "Тип",
        "text.service": "Послуга",
        "text.date": "Дата",
        "text.text": "Текст",
        "text.search": "Пошук",
        "text.date.from": "Дата від",
        "text.date.to": "Дата до",
        "text.serial.number.or.mac": "Серійник або MAC",
        "text.image.folder": "Папка зображень",
        "text.folder.path": "Шлях до папки",
        "text.save.folder": "Зберегти папку",
        "text.ai.agents": "AI-агенти",
        "text.ai.agent": "AI-агент",
        "text.add.agent": "Додати агента",
        "text.provider": "Постачальник",
        "text.api.token": "API-токен",
        "text.interface.language": "Мова інтерфейсу",
        "text.apply": "Застосувати",
        "text.ukrainian": "Українська",
        "text.updates": "Оновлення",
        "text.check.for.updates": "Перевірка оновлень",
        "text.about": "Про програму",
        "text.by.day": "По днях",
        "text.by.month": "По місяцях",
        "text.month": "Місяць",
        "text.total.requests": "Усього заявок: ",
        "text.receipt": "Прийом",
        "text.issue": "Видача",
        "text.receipts.and.issues": "Прийом та видача",
        "text.popular.models": "Популярні моделі",
        "text.local.ocr.2": "Локальне OCR",
        "text.ai.recognition": "AI-розпізнавання",
        "text.recognize.with.ai": "Розпізнати AI",
        "text.copy": "Копіювати",
        "text.view": "Переглянути",
        "text.rotate.90.clockwise": "Повернути на 90° вправо",
        "text.delete.photo": "Видалити фото",
        "text.next": "Наступна",
        "text.previous": "Попередня",
        "text.language": "Мова",
        "text.choose.an.agent": "Виберіть агента",
        "text.add.an.agent.first": "Спочатку додайте агента",
        "text.choose.an.image.and.an.ai.agent":
            "Виберіть зображення й AI-агента.",
        "text.add.an.ai.agent.in.settings":
            "Додайте AI-агента у налаштуваннях.",
        "text.local.recognition": "Локальне розпізнавання…",
        "text.please.wait": "Будь ласка, зачекайте.",
        "text.no.text.found": "Текст не знайдено.",
        "text.local.ocr.is.ready": "Локальний OCR готовий",
        "text.ocr.error": "Помилка OCR",
        "text.ai.error": "Помилка AI",
        "text.loading.images": "Завантажуємо зображення…",
        "text.loading.statistics": "Завантажуємо статистику…",
        "text.not.loaded.yet": "Ще не завантажено",
        "text.there.are.no.records.to.build.a.chart.yet":
            "Ще немає записів для побудови графіка.",
        "text.open.the.tab.to.build.charts":
            "Відкрийте вкладку, щоб побудувати графіки.",
    };

    const sourceKeys = new Map(
        Object.entries(sourceTexts).map(([key, value]) => [value, key]),
    );

    const translations = {
        en: {
            "text.choose.an.image": "Choose an image",
            "text.local.ocr": "Local OCR",
            "text.the.result.will.appear.here": "The result will appear here.",
            "text.folder": "Folder:",
            "text.serial.numbers.mac.text": "Serial numbers / MAC / text",
            "text.contract.number": "Contract number",
            "text.for.example.123.45": "For example, 123/45",
            "text.operation": "Operation",
            "text.add.record.to.database": "Add record to database",
            "text.export.to.excel": "Export to Excel",
            "text.number.of.requests.receipts.and.issues.from.saved.equipment.records":
                "Number of requests, receipts, and issues from saved equipment records.",
            "text.statistics.for.the.current.month.from.the.1st.day":
                "Statistics for the current month — from the 1st day.",
            "text.number.of.operations.by.day": "Number of operations by day.",
            "text.number.of.operations.by.month":
                "Number of operations by month.",
            "text.internet.and.television": "Internet and television.",
            "text.number.of.requests.by.equipment.name":
                "Number of requests by equipment name.",
            "text.date.and.time": "Date and time",
            "text.update.checking.is.available.in.the.installed.version.of.the.application":
                "Update checking is available in the installed version of the application.",
            "text.local.application.for.equipment.tracking.and.serial.number.recognition":
                "Local application for equipment tracking and serial-number recognition.",
            "text.version": "Version",
            "text.developer": "Developer",
            "text.website": "Website",
            "text.open.website": "Open website",
            "text.equipment.and.data": "Equipment and data",
            "text.local.and.ai.recognition": "Local and AI recognition",
            "text.recognition": "Recognition",
            "text.equipment": "Equipment",
            "text.models": "Models",
            "text.statistics": "Statistics",
            "text.settings": "Settings",
            "text.images": "Images",
            "text.results": "Results",
            "text.open": "Open",
            "text.choose": "Choose",
            "text.choose.folder": "Choose folder",
            "text.save": "Save",
            "text.cancel": "Cancel",
            "text.close": "Close",
            "text.delete": "Delete",
            "text.edit": "Edit",
            "text.add.to.database": "Add to database",
            "text.add.unformatted.to.database": "Add unformatted to database",
            "text.add.model": "Add model",
            "text.model.directory": "Model directory",
            "text.all.types": "All types",
            "text.all.services": "All services",
            "text.modem": "Modem",
            "text.set.top.box": "Set-top box",
            "text.internet": "Internet",
            "text.television": "Television",
            "text.name": "Name",
            "text.model": "Model",
            "text.type": "Type",
            "text.service": "Service",
            "text.date": "Date",
            "text.text": "Text",
            "text.search": "Search",
            "text.date.from": "Date from",
            "text.date.to": "Date to",
            "text.serial.number.or.mac": "Serial number or MAC",
            "text.image.folder": "Image folder",
            "text.folder.path": "Folder path",
            "text.save.folder": "Save folder",
            "text.ai.agents": "AI agents",
            "text.ai.agent": "AI agent",
            "text.add.agent": "Add agent",
            "text.provider": "Provider",
            "text.api.token": "API token",
            "text.interface.language": "Interface language",
            "text.apply": "Apply",
            "text.ukrainian": "Ukrainian",
            "text.updates": "Updates",
            "text.check.for.updates": "Check for updates",
            "text.about": "About",
            "text.by.day": "By day",
            "text.by.month": "By month",
            "text.month": "Month",
            "text.total.requests": "Total requests: ",
            "text.receipt": "Receipt",
            "text.issue": "Issue",
            "text.receipts.and.issues": "Receipts and issues",
            "text.popular.models": "Popular models",
            "text.local.ocr.2": "Local OCR",
            "text.ai.recognition": "AI recognition",
            "text.recognize.with.ai": "Recognize with AI",
            "text.copy": "Copy",
            "text.view": "View",
            "text.rotate.90.clockwise": "Rotate 90° clockwise",
            "text.delete.photo": "Delete photo",
            "text.next": "Next",
            "text.previous": "Previous",
            "text.language": "Language",
            "text.choose.an.agent": "Choose an agent",
            "text.add.an.agent.first": "Add an agent first",
            "text.choose.an.image.and.an.ai.agent":
                "Choose an image and an AI agent.",
            "text.add.an.ai.agent.in.settings": "Add an AI agent in Settings.",
            "text.local.recognition": "Local recognition…",
            "text.please.wait": "Please wait.",
            "text.no.text.found": "No text found.",
            "text.local.ocr.is.ready": "Local OCR is ready",
            "text.ocr.error": "OCR error",
            "text.ai.error": "AI error",
            "text.loading.images": "Loading images…",
            "text.loading.statistics": "Loading statistics…",
            "text.not.loaded.yet": "Not loaded yet",
            "text.there.are.no.records.to.build.a.chart.yet":
                "There are no records to build a chart yet.",
            "text.open.the.tab.to.build.charts":
                "Open the tab to build charts.",
        },
        pl: {
            "text.choose.an.image": "Wybierz obraz",
            "text.local.ocr": "Lokalne OCR",
            "text.the.result.will.appear.here": "Wynik pojawi się tutaj.",
            "text.folder": "Folder:",
            "text.serial.numbers.mac.text": "Numery seryjne / MAC / tekst",
            "text.contract.number": "Numer umowy",
            "text.for.example.123.45": "Na przykład 123/45",
            "text.operation": "Operacja",
            "text.add.record.to.database": "Dodaj rekord do bazy",
            "text.export.to.excel": "Eksportuj do Excela",
            "text.number.of.requests.receipts.and.issues.from.saved.equipment.records":
                "Liczba zgłoszeń, przyjęć i wydań według zapisanych rekordów sprzętu.",
            "text.statistics.for.the.current.month.from.the.1st.day":
                "Statystyki za bieżący miesiąc — od 1. dnia.",
            "text.number.of.operations.by.day": "Liczba operacji według dni.",
            "text.number.of.operations.by.month":
                "Liczba operacji według miesięcy.",
            "text.internet.and.television": "Internet i telewizja.",
            "text.number.of.requests.by.equipment.name":
                "Liczba zgłoszeń według nazwy sprzętu.",
            "text.date.and.time": "Data i godzina",
            "text.update.checking.is.available.in.the.installed.version.of.the.application":
                "Sprawdzanie aktualizacji jest dostępne w zainstalowanej wersji aplikacji.",
            "text.local.application.for.equipment.tracking.and.serial.number.recognition":
                "Lokalna aplikacja do ewidencji sprzętu i rozpoznawania numerów seryjnych.",
            "text.version": "Wersja",
            "text.developer": "Twórca",
            "text.website": "Strona internetowa",
            "text.open.website": "Otwórz stronę",
            "text.equipment.and.data": "Sprzęt i dane",
            "text.local.and.ai.recognition": "Rozpoznawanie lokalne i AI",
            "text.recognition": "Rozpoznawanie",
            "text.equipment": "Sprzęt",
            "text.models": "Modele",
            "text.statistics": "Statystyki",
            "text.settings": "Ustawienia",
            "text.images": "Obrazy",
            "text.results": "Wyniki",
            "text.open": "Otwórz",
            "text.choose": "Wybierz",
            "text.choose.folder": "Wybierz folder",
            "text.save": "Zapisz",
            "text.cancel": "Anuluj",
            "text.close": "Zamknij",
            "text.delete": "Usuń",
            "text.edit": "Edytuj",
            "text.add.to.database": "Dodaj do bazy",
            "text.add.unformatted.to.database":
                "Dodaj do bazy bez formatowania",
            "text.add.model": "Dodaj model",
            "text.model.directory": "Katalog modeli",
            "text.all.types": "Wszystkie typy",
            "text.all.services": "Wszystkie usługi",
            "text.modem": "Modem",
            "text.set.top.box": "Dekoder",
            "text.internet": "Internet",
            "text.television": "Telewizja",
            "text.name": "Nazwa",
            "text.model": "Model",
            "text.type": "Typ",
            "text.service": "Usługa",
            "text.date": "Data",
            "text.text": "Tekst",
            "text.search": "Szukaj",
            "text.date.from": "Data od",
            "text.date.to": "Data do",
            "text.serial.number.or.mac": "Numer seryjny lub MAC",
            "text.image.folder": "Folder obrazów",
            "text.folder.path": "Ścieżka folderu",
            "text.save.folder": "Zapisz folder",
            "text.ai.agents": "Agenci AI",
            "text.ai.agent": "Agent AI",
            "text.add.agent": "Dodaj agenta",
            "text.provider": "Dostawca",
            "text.api.token": "Token API",
            "text.interface.language": "Język interfejsu",
            "text.apply": "Zastosuj",
            "text.ukrainian": "Ukraiński",
            "text.updates": "Aktualizacje",
            "text.check.for.updates": "Sprawdź aktualizacje",
            "text.about": "O programie",
            "text.by.day": "Według dni",
            "text.by.month": "Według miesięcy",
            "text.month": "Miesiąc",
            "text.total.requests": "Łącznie zgłoszeń: ",
            "text.receipt": "Przyjęcie",
            "text.issue": "Wydanie",
            "text.receipts.and.issues": "Przyjęcia i wydania",
            "text.popular.models": "Popularne modele",
            "text.local.ocr.2": "Lokalne OCR",
            "text.ai.recognition": "Rozpoznawanie AI",
            "text.recognize.with.ai": "Rozpoznaj przez AI",
            "text.copy": "Kopiuj",
            "text.view": "Wyświetl",
            "text.rotate.90.clockwise": "Obróć o 90° w prawo",
            "text.delete.photo": "Usuń zdjęcie",
            "text.next": "Następna",
            "text.previous": "Poprzednia",
            "text.language": "Język",
            "text.choose.an.agent": "Wybierz agenta",
            "text.add.an.agent.first": "Najpierw dodaj agenta",
            "text.choose.an.image.and.an.ai.agent":
                "Wybierz obraz i agenta AI.",
            "text.add.an.ai.agent.in.settings":
                "Dodaj agenta AI w ustawieniach.",
            "text.local.recognition": "Lokalne rozpoznawanie…",
            "text.please.wait": "Proszę czekać.",
            "text.no.text.found": "Nie znaleziono tekstu.",
            "text.local.ocr.is.ready": "Lokalne OCR gotowe",
            "text.ocr.error": "Błąd OCR",
            "text.ai.error": "Błąd AI",
            "text.loading.images": "Ładowanie obrazów…",
            "text.loading.statistics": "Ładowanie statystyk…",
            "text.not.loaded.yet": "Jeszcze nie załadowano",
            "text.there.are.no.records.to.build.a.chart.yet":
                "Brak rekordów do utworzenia wykresu.",
            "text.open.the.tab.to.build.charts":
                "Otwórz kartę, aby utworzyć wykresy.",
        },
    };

    const locale =
        window.initialLocale || document.documentElement.lang || "uk";
    const dictionary = translations[locale] || {};

    const replace = (value) => {
        const key = sourceKeys.get(value) || value;
        const translated = window.i18n?.[key] ?? dictionary[key];

        if (translated) return translated;

        const totalRequests = value.match(/^Усього заявок:\s*(\d+)$/);
        if (totalRequests) {
            const totalPrefixKey = sourceKeys.get("Усього заявок: ");
            const totalPrefix = totalPrefixKey
                ? dictionary[totalPrefixKey]
                : undefined;

            return (totalPrefix || "Усього заявок: ") + totalRequests[1];
        }

        return value;
    };

    window.translate = (value, replacements = {}) =>
        String(replace(value)).replace(
            /:([A-Za-z_]+)/g,
            (_, key) => replacements[key] ?? ":" + key,
        );

    const translateElement = (element) => {
        if (element.nodeType === Node.TEXT_NODE) {
            const original = element.nodeValue;
            const leadingWhitespace = original.match(/^\s*/)[0];

            element.nodeValue = leadingWhitespace + replace(original.trim());
            return;
        }

        if (
            element.nodeType !== Node.ELEMENT_NODE ||
            ["SCRIPT", "STYLE"].includes(element.tagName)
        ) {
            return;
        }

        ["placeholder", "title", "aria-label"].forEach((attribute) => {
            if (element.hasAttribute(attribute)) {
                element.setAttribute(
                    attribute,
                    replace(element.getAttribute(attribute)),
                );
            }
        });

        element.childNodes.forEach(translateElement);
    };

    const translateDocument = () => translateElement(document.body);

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", translateDocument, {
            once: true,
        });
    } else {
        translateDocument();
    }

    new MutationObserver((records) =>
        records.forEach((record) =>
            record.addedNodes.forEach(translateElement),
        ),
    ).observe(document.documentElement, { childList: true, subtree: true });
})();
