// Маска телефона +7 (999) 123-45-67 для полей с data-phone-mask.
// Храним в БД в едином формате +7XXXXXXXXXX — нормализует бэкенд.

function formatPhone(value) {
    let digits = value.replace(/\D/g, '');

    if (digits === '') {
        return '';
    }

    if (digits.startsWith('8')) {
        digits = '7' + digits.slice(1);
    } else if (!digits.startsWith('7')) {
        // Пользователь начал вводить с «9» (или любой другой цифры) — считаем
        // это началом российского номера и автоматически подставляем «7».
        digits = '7' + digits;
    }

    digits = digits.slice(0, 11);

    let result = '+7';

    if (digits.length > 1) result += ' (' + digits.slice(1, 4);
    if (digits.length >= 4) result += ') ' + digits.slice(4, 7);
    if (digits.length >= 7) result += '-' + digits.slice(7, 9);
    if (digits.length >= 9) result += '-' + digits.slice(9, 11);

    return result;
}

function initPhoneMasks() {
    document.querySelectorAll('input[data-phone-mask]').forEach((input) => {
        // Предзаполненное значение (например, из профиля) приводим к маске.
        if (input.value) {
            input.value = formatPhone(input.value);
        }

        input.addEventListener('input', () => {
            input.value = formatPhone(input.value);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPhoneMasks);
} else {
    initPhoneMasks();
}
