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

function toast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const el = document.createElement('div');
    el.className =
        'pointer-events-auto flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg transition-all duration-300 ' +
        (type === 'error' ? 'bg-red-600' : 'bg-brand-600');
    el.textContent = message;
    container.appendChild(el);

    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(-8px)';
        setTimeout(() => el.remove(), 300);
    }, 2500);
}

/**
 * Добавить товар в корзину (глобальная функция — используется в Alpine
 * на карточках товаров и странице товара).
 */
async function addToCart(productId, quantity = 1) {
    const { ok, payload } = await cartRequest('/cart/add', { product_id: productId, quantity });

    if (ok && payload.success) {
        updateCartCounter(payload.count);
        toast(payload.message || 'Товар добавлен в корзину');
    } else {
        toast(payload.message || 'Не удалось добавить товар в корзину', 'error');
    }
}

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

window.addToCart = addToCart;
window.dshCart = { addToCart, updateCartCounter, toast };

export { cartRequest, toast, updateCartCounter, formatPrice };

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCartPage);
} else {
    initCartPage();
}
