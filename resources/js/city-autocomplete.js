// Автопоиск города для полей доставки (checkout, редактирование заказа).
// Кастомный выпадающий список в стиле витрины вместо нативного <datalist>:
// при вводе показываются совпадения (до 10), выбор — по клику/Enter/стрелкам.

/**
 * @param {Array<{name: string, region: string|null}>} cities
 * @param {string} initial
 */
function cityAutocomplete(cities, initial = '') {
    return {
        query: initial,
        open: false,
        highlight: 0,
        filtered: [],

        filter() {
            const q = this.query.trim().toLowerCase();

            if (q === '') {
                this.filtered = [];
                this.open = false;

                return;
            }

            this.filtered = cities
                .filter((city) => {
                    const haystack = (city.name + ' ' + (city.region ?? '')).toLowerCase();

                    return haystack.includes(q);
                })
                .slice(0, 10);

            this.highlight = 0;
            this.open = this.filtered.length > 0;
        },

        label(city) {
            return city.region ? city.name + ' (' + city.region + ')' : city.name;
        },

        select(city) {
            this.query = city.name;
            this.open = false;
        },

        selectHighlighted() {
            if (this.open && this.filtered[this.highlight]) {
                this.select(this.filtered[this.highlight]);
            }
        },

        move(direction) {
            if (!this.open || this.filtered.length === 0) {
                return;
            }

            this.highlight = (this.highlight + direction + this.filtered.length) % this.filtered.length;
        },
    };
}

window.cityAutocomplete = cityAutocomplete;
