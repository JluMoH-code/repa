// Корзина: AJAX-добавление с карточек/страницы товара, счётчик в шапке,
// обновление количества/удаление/очистка на странице корзины, toast-уведомления.

const CSRF_TOKEN = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function formatPrice(kopecks) {
    return new Intl.NumberFormat('ru-RU').format(Math.round(kopecks / 100)) + ' ₽';
}

async function cartRequest(url, data) {
    let response;
    try {
        response = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN(),
            },
            body: JSON.stringify(data),
        });
    } catch (error) {
        return { ok: false, payload: { message: 'Не удалось выполнить запрос. Попробуйте ещё раз.' } };
    }

    let payload = {};
    try {
        payload = await response.json();
    } catch (error) {
        // Пустой/не-JSON ответ — считаем ошибкой.
    }

    return { ok: response.ok, payload };
}

function updateCartCounter(count) {
    const badge = document.getElementById('cart-count');
    if (badge) badge.textContent = count;
}

// Единственный видимый тост: новый всегда заменяет текущий (и обновляет его
// текст, если тот ещё на экране), таймер показа перезапускается — при быстрых
// повторных кликах тосты не накапливаются и не заполняют экран.
let toastEl = null;
let toastTimer = null;

function toast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    if (!toastEl || !toastEl.isConnected) {
        toastEl = document.createElement('div');
        toastEl.className =
            'pointer-events-auto flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg transition-all duration-300';
        container.appendChild(toastEl);
    }

    toastEl.textContent = message;
    toastEl.classList.toggle('bg-red-600', type === 'error');
    toastEl.classList.toggle('bg-brand-600', type !== 'error');
    toastEl.style.opacity = '1';
    toastEl.style.transform = '';

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        if (!toastEl) return;

        toastEl.style.opacity = '0';
        toastEl.style.transform = 'translateY(8px)';
        setTimeout(() => {
            if (toastEl && toastEl.isConnected) {
                toastEl.remove();
            }
            toastEl = null;
        }, 300);
    }, 1500);
}

/**
 * Добавить товар в корзину (глобальная функция — используется в Alpine
 * на карточках товаров и странице товара).
 *
 * @param {number} productId
 * @param {number} [quantity=1]
 * @param {(payload: object) => void} [onSuccess] — вызывается после успешного
 *   добавления с ответом сервера (содержит `quantity` — количество товара в корзине)
 * @returns {Promise<boolean>}
 */
async function addToCart(productId, quantity = 1, onSuccess = null) {
    const { ok, payload } = await cartRequest('/cart/add', { product_id: productId, quantity });

    if (ok && payload.success) {
        updateCartCounter(payload.count);
        // При повторном добавлении того же товара тост обновляется (см. toast()):
        // вместо новой «стопки» показываем актуальное количество в корзине.
        toast(typeof payload.quantity === 'number' && payload.quantity > 1
            ? `Товар в корзине: ${payload.quantity} шт.`
            : (payload.message || 'Товар добавлен в корзину'));
        if (onSuccess) onSuccess(payload);

        return true;
    }

    toast(payload.message || 'Не удалось добавить товар в корзину', 'error');

    return false;
}

/**
 * Изменить количество позиции в корзине (для stepper'а на карточках/странице товара).
 */
async function updateCartQuantity(productId, quantity, onSuccess = null) {
    const { ok, payload } = await cartRequest('/cart/update', { product_id: productId, quantity });

    if (ok && payload.success) {
        updateCartCounter(payload.count);
        if (onSuccess) onSuccess(payload);

        return true;
    }

    toast(payload.message || 'Не удалось обновить количество', 'error');

    return false;
}

/**
 * Удалить товар из корзины (для stepper'а на карточках/странице товара).
 */
async function removeFromCart(productId, onSuccess = null) {
    const { ok, payload } = await cartRequest('/cart/remove', { product_id: productId });

    if (ok && payload.success) {
        updateCartCounter(payload.count);
        toast(payload.message || 'Товар удалён из корзины');
        if (onSuccess) onSuccess(payload);

        return true;
    }

    toast(payload.message || 'Не удалось удалить товар', 'error');

    return false;
}

/**
 * Синхронизация состояния корзины с сервером: браузер при возврате «назад»
 * может восстановить страницу из bfcache или из HTTP-кэша со старым DOM
 * (кнопка «Купить», старый счётчик), поэтому всегда подтягиваем актуальные
 * количества и оповещаем страницу событием `cart-synced`.
 */
async function syncCartState() {
    let payload = {};
    try {
        const response = await fetch('/cart/quantities', { headers: { Accept: 'application/json' } });
        payload = await response.json();
    } catch (error) {
        return;
    }

    if (typeof payload.count === 'number') {
        updateCartCounter(payload.count);
    }

    if (! payload.quantities || typeof payload.quantities !== 'object') {
        return;
    }

    window.dispatchEvent(new CustomEvent('cart-synced', { detail: payload }));
}

// Возврат «назад»: страница может прийти из bfcache/HTTP-кэша со старым
// состоянием корзины. Синхронизируем всегда (страницу корзины перезагружаем
// целиком — её DOM точечно обновлять сложно). Небольшая задержка нужна, чтобы
// Alpine успел навесить слушатели cart-synced.
//
// ВАЖНО: pageshow срабатывает при КАЖДОЙ загрузке страницы, а не только при
// восстановлении из bfcache. Reload нужен только для bfcache-восстановления
// (event.persisted === true) — иначе обычный заход на /cart уходит в бесконечный
// цикл перезагрузки и оформить заказ невозможно.
window.addEventListener('pageshow', (event) => {
    if (document.getElementById('cart-page') && event.persisted) {
        window.location.reload();

        return;
    }

    setTimeout(syncCartState, 50);
});

// Возврат на вкладку: корзина могла измениться в другой вкладке.
window.addEventListener('focus', () => {
    setTimeout(syncCartState, 50);
});

// Инициализация страницы корзины: перехватываем формы update/remove/clear
// и обновляем суммы на странице без перезагрузки.
function initCartPage() {
    const root = document.getElementById('cart-page');
    if (!root) return;

    const updateTotals = (payload, productId, quantity = null) => {
        if (typeof payload.count === 'number') {
            updateCartCounter(payload.count);
        }

        if (typeof payload.total === 'number') {
            const totalEl = document.getElementById('cart-total');
            if (totalEl) totalEl.textContent = formatPrice(payload.total);
        }

        if (typeof payload.line_total === 'number') {
            const lineEl = root.querySelector(`[data-line-total][data-product="${productId}"]`);
            if (lineEl) lineEl.textContent = formatPrice(payload.line_total);

            const summaryTotal = root.querySelector(`[data-summary-total][data-product="${productId}"]`);
            if (summaryTotal) summaryTotal.textContent = formatPrice(payload.line_total);

            if (quantity !== null) {
                const summaryQty = root.querySelector(`[data-summary-qty][data-product="${productId}"]`);
                if (summaryQty) summaryQty.textContent = quantity;
            }
        }
    };

    root.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-cart-action]');
        if (!form) return;

        event.preventDefault();

        const data = Object.fromEntries(new FormData(form).entries());
        const { ok, payload } = await cartRequest(form.action, data);

        if (!ok || !payload.success) {
            toast(payload.message || 'Не удалось выполнить операцию', 'error');

            return;
        }

        updateTotals(payload, data.product_id, data.quantity ? parseInt(data.quantity, 10) : null);

        if (form.dataset.cartAction === 'remove') {
            form.closest('[data-cart-line]')?.remove();
            root.querySelector(`[data-summary-row][data-product="${data.product_id}"]`)?.remove();
        }

        if (payload.count === 0) {
            window.location.reload();
        }
    });

    // Кнопки +/− меняют значение input и отправляют форму на обновление.
    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-qty-step]');
        if (!button) return;

        const input = button.closest('.cart-qty')?.querySelector('input[name="quantity"]');
        if (!input) return;

        const step = parseInt(button.dataset.qtyStep, 10);
        input.value = Math.max(1, Math.min(99, (parseInt(input.value, 10) || 1) + step));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // Ручное изменение количества (change) — обновление корзины.
    root.addEventListener('change', (event) => {
        const input = event.target.closest('input[name="quantity"]');
        if (!input) return;

        input.closest('form[data-cart-action="update"]')?.requestSubmit();
    });
}

// Глобальные функции для Alpine-выражений на карточках товаров и странице
// товара (x-data вызывает их по имени). addToCart/updateCartQuantity/removeFromCart
// вывешены на window — Alpine-выражения резолвят идентификаторы через window,
// и без этого «−» на stepper'е падал с ReferenceError.
window.addToCart = addToCart;
window.updateCartQuantity = updateCartQuantity;
window.removeFromCart = removeFromCart;
window.dshCart = { addToCart, updateCartQuantity, removeFromCart, syncCartState, updateCartCounter, toast };

export { cartRequest, toast, updateCartCounter, formatPrice };

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCartPage);
} else {
    initCartPage();
}
