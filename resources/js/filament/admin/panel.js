// Страховка от «зависшей» кнопки при загрузке файлов в формах Filament.
//
// Проблема: Filament-кнопки (в т.ч. «Сохранить») держат состояние
// «Загрузка файла...», пока не придёт событие form-processing-finished
// от компонента FileUpload (см. vendor/filament/support/.../form-button.js
// и vendor/filament/forms/.../file-upload.js). В ряде случаев это событие
// не приходит вовсе, и кнопка остаётся зависшей навсегда:
//   1) загрузка доходит до 100% и «застревает» — нет ни finish, ни error;
//   2) файл (или строка Repeater с загрузкой) удалён до завершения загрузки —
//      компонент уничтожается и не успевает «отщёлкнуть» обработку;
//   3) Livewire-загрузка завершается ошибкой — FilePond переводит файл
//      в состояние ERROR, а form-processing-finished так и не диспатчится
//      (обработчик FileUpload слушает processfile/abort/revert/removefile,
//      но не error).
//
// Решение — три независимые страховки (без правки vendor):
//   - livewire-upload-error / livewire-upload-cancel → сразу снимаем
//     состояние обработки с формы;
//   - удаление блока .fi-fo-file-upload из DOM (строка Repeater удалена
//     во время загрузки) → снимаем состояние, если в форме не осталось
//     ни одной загрузки;
//   - watchdog: если form-processing-started не завершился за 60 секунд,
//     принудительно диспатчим form-processing-finished.

(() => {
    if (window.__repaAdminUploadGuard) {
        return;
    }
    window.__repaAdminUploadGuard = true;

    const STALL_TIMEOUT = 60000;

    /** @type {WeakMap<HTMLFormElement, {count: number, watchdog: number | null}>} */
    const states = new WeakMap();
    /** @type {WeakMap<Element, HTMLFormElement | null>} */
    const formOfUpload = new WeakMap();

    const getState = (form) => {
        let state = states.get(form);
        if (! state) {
            state = { count: 0, watchdog: null };
            states.set(form, state);
        }

        return state;
    };

    const forceFinish = (form) => {
        const state = getState(form);

        if (state.count <= 0) {
            return;
        }

        state.count = 0;

        if (state.watchdog) {
            clearTimeout(state.watchdog);
            state.watchdog = null;
        }

        form.dispatchEvent(new CustomEvent('form-processing-finished'));
    };

    const watchForm = (form) => {
        if (form.__repaUploadGuard) {
            return;
        }
        form.__repaUploadGuard = true;

        form.addEventListener('form-processing-started', () => {
            const state = getState(form);
            state.count += 1;

            if (state.watchdog) {
                clearTimeout(state.watchdog);
            }

            state.watchdog = setTimeout(() => forceFinish(form), STALL_TIMEOUT);
        });

        form.addEventListener('form-processing-finished', () => {
            const state = getState(form);
            state.count = Math.max(0, state.count - 1);

            if (state.count === 0 && state.watchdog) {
                clearTimeout(state.watchdog);
                state.watchdog = null;
            }
        });
    };

    // Ошибка/отмена Livewire-загрузки: FilePond не диспатчит
    // form-processing-finished — снимаем состояние сразу.
    document.addEventListener('livewire-upload-error', (event) => {
        const form = event.target?.closest?.('form');

        if (form) {
            setTimeout(() => forceFinish(form), 0);
        }
    }, true);

    document.addEventListener('livewire-upload-cancel', (event) => {
        const form = event.target?.closest?.('form');

        if (form) {
            setTimeout(() => forceFinish(form), 0);
        }
    }, true);

    const collectUploads = (node) => {
        if (node.matches?.('.fi-fo-file-upload')) {
            return [node];
        }

        return Array.from(node.querySelectorAll?.('.fi-fo-file-upload') ?? []);
    };

    const observer = new MutationObserver((mutations) => {
        const affectedForms = new Set();

        for (const mutation of mutations) {
            for (const node of mutation.addedNodes) {
                if (! (node instanceof Element)) {
                    continue;
                }

                collectUploads(node).forEach((upload) => {
                    formOfUpload.set(upload, upload.closest('form'));
                });

                const forms = node.matches?.('form')
                    ? [node]
                    : Array.from(node.querySelectorAll?.('form') ?? []);

                forms.forEach(watchForm);
            }

            for (const node of mutation.removedNodes) {
                if (! (node instanceof Element)) {
                    continue;
                }

                collectUploads(node).forEach((upload) => {
                    const form = formOfUpload.get(upload);

                    if (form) {
                        affectedForms.add(form);
                    }
                });
            }
        }

        document.querySelectorAll('form').forEach(watchForm);

        if (affectedForms.size === 0) {
            return;
        }

        // Даём morphdom/Livewire достроить DOM, затем проверяем состояние.
        setTimeout(() => {
            affectedForms.forEach((form) => {
                if (! form.isConnected) {
                    return;
                }

                const state = getState(form);

                if (state.count <= 0) {
                    return;
                }

                if (! form.querySelector('.fi-fo-file-upload')) {
                    forceFinish(form);
                }
            });
        }, 50);
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });

    document.querySelectorAll('form').forEach(watchForm);
    document.querySelectorAll('.fi-fo-file-upload').forEach((upload) => {
        formOfUpload.set(upload, upload.closest('form'));
    });
})();
