(() => {
    const setup = document.querySelector("#first-run-setup");
    if (!setup) return;

    const t = (key) => window.translate?.(key) ?? key;
    const accepted = setup.querySelector("#setup-accepted");
    const directory = setup.querySelector("#setup-image-directory");
    const locale = setup.querySelector("#setup-locale");
    const choose = setup.querySelector("#choose-setup-directory");
    const complete = setup.querySelector("#complete-setup");
    const skip = setup.querySelector("#skip-setup");
    const message = setup.querySelector("#setup-message");
    const csrf =
        document.querySelector('meta[name="csrf-token"]')?.content ?? "";
    const isDevelopment = document.body.dataset.environment === "local";

    const sync = () => {
        const canComplete =
            accepted.checked && directory.value.trim().length > 0;
        complete.disabled = !canComplete;
        complete.setAttribute("aria-disabled", String(!canComplete));
    };

    const request = async (url, method = "POST", data = {}) => {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 12000);

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify(data),
                signal: controller.signal,
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(payload.message || t("operation_failed"));
            }

            return payload;
        } finally {
            window.clearTimeout(timeout);
        }
    };

    accepted.addEventListener("change", sync, true);

    locale.addEventListener("change", async () => {
        locale.disabled = true;

        try {
            await request("/locale", "PUT", { locale: locale.value });
            window.location.reload();
        } catch (error) {
            message.textContent = error.message;
            locale.disabled = false;
        }
    });

    choose.addEventListener(
        "click",
        async (event) => {
            event.preventDefault();
            event.stopImmediatePropagation();
            choose.disabled = true;
            message.textContent = t("opening_folder_picker");

            try {
                const result = await request("/image-directory/choose");

                if (result.path) directory.value = result.path;
                message.textContent = result.path
                    ? t("folder_selected")
                    : t("selection_cancelled");
            } catch (error) {
                message.textContent = error.message;
            } finally {
                choose.disabled = false;
                sync();
            }
        },
        true,
    );

    skip.addEventListener(
        "click",
        async () => {
            skip.disabled = true;
            message.textContent = t("opening_app");

            try {
                await request("/setup", "POST", {
                    accepted: true,
                    path: directory.value,
                });
                setup.remove();
            } catch (error) {
                message.textContent = error.message;
                skip.disabled = false;
            }
        },
        true,
    );

    complete.addEventListener(
        "click",
        async (event) => {
            event.preventDefault();
            event.stopImmediatePropagation();
            complete.disabled = true;
            message.textContent = t("saving_settings");

            try {
                await request("/setup", "POST", {
                    accepted: accepted.checked,
                    path: directory.value,
                });
                setup.remove();

                if (!isDevelopment) window.location.reload();
            } catch (error) {
                message.textContent = error.message;
                sync();
            }
        },
        true,
    );

    sync();
})();
