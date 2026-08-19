// Избранное: переключение сердечек на карточках, страница избранного,
// счётчик в шапке/сайдбаре.

import { cartRequest, toast, updateCartCounter } from './cart.js';

function updateFavoritesCount(count) {
    document.querySelectorAll('#favorites-count').forEach((el) => {
        el.textContent = count;
    });
}

function setFavoriteButton(button, active) {
    button.dataset.active = active ? '1' : '0';
    button.classList.toggle('border-red-200', active);
    button.classList.toggle('bg-red-50', active);
    button.classList.toggle('text-red-500', active);
    button.classList.toggle('border-slate-200', !active);
    button.classList.toggle('text-slate-400', !active);

    const icon = button.querySelector('.favorite-heart-icon');
    if (icon) icon.setAttribute('fill', active ? 'currentColor' : 'none');
}

async function toggleFavorite(productId) {
    const { ok, payload } = await cartRequest('/favorites/toggle', { product_id: productId });

    if (!ok || !payload.success) {
        toast(payload.message || 'Не удалось обновить избранное', 'error');

        return;
    }

    updateFavoritesCount(payload.count);
    document.querySelectorAll(`[data-favorite-id="${productId}"]`).forEach((button) => setFavoriteButton(button, payload.favorite));
    toast(payload.favorite ? 'Товар добавлен в избранное' : 'Товар удалён из избранного');
}

window.toggleFavorite = toggleFavorite;

// Сердечки на карточках товаров (глобальная делегация).
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-favorite-id]');
    if (!button) return;

    toggleFavorite(button.dataset.favoriteId);
});

// Страница избранного: удаление позиции без перезагрузки.
function initFavoritesPage() {
    const root = document.getElementById('favorites-page');
    if (!root) return;

    root.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-favorite-remove]');
        if (!button) return;

        const productId = button.dataset.favoriteRemove;
        const { ok, payload } = await cartRequest('/favorites/remove', { product_id: productId });

        if (!ok || !payload.success) {
            toast(payload.message || 'Не удалось удалить товар из избранного', 'error');

            return;
        }

        updateFavoritesCount(payload.count);
        root.querySelector(`[data-favorite-row="${productId}"]`)?.remove();

        if (payload.count === 0) {
            window.location.reload();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFavoritesPage);
} else {
    initFavoritesPage();
}
