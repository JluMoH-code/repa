<div class="rp-catalog-heading">
    <div><span class="rp-eyebrow">Каталог магазина</span><h1>Товары</h1><p>Соберите понятные карточки: фото, фасовка, цена и характеристики семян.</p></div>
    <div class="rp-actions"><a class="rp-button" href="{{ \App\Filament\Pages\Inventory::getUrl() }}">Открыть склад</a><a class="rp-button rp-button-primary" href="{{ \App\Filament\Resources\Products\ProductResource::getUrl('create') }}">+ Добавить товар</a></div>
</div>
<div class="rp-catalog-guide">
    <div><strong>Карточка и публикация</strong><span>Нажмите на товар, чтобы изменить описание, цену или фото.</span></div>
    <div><strong>Количество упаковок</strong><span>Поступления, пересчёт и продажи оформляются в разделе «Остатки».</span></div>
    <a href="{{ \App\Filament\Pages\CatalogSetup::getUrl() }}">Настроить каталог →</a>
</div>
