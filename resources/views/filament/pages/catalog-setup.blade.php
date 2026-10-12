<x-filament-panels::page>
<div class="rp-catalog-heading"><div><span class="rp-eyebrow">Порядок в магазине</span><h2>Помогите покупателю найти нужные семена</h2><p>Настройте разделы, бренды и характеристики. Сами товары редактируются отдельно.</p></div><a class="rp-button" href="{{ \App\Filament\Resources\Products\ProductResource::getUrl() }}">К товарам →</a></div>
<div class="rp-setup-grid">
@foreach([
['Разделы магазина','Категории','Овощи, цветы, травы — сгруппируйте товары так, как их ищут покупатели.',\App\Models\Category::count(),\App\Filament\Resources\Categories\CategoryResource::getUrl(),'01'],
['Бренды семян','Производители','Укажите производителей один раз, затем выбирайте их в карточке товара.',\App\Models\Manufacturer::count(),\App\Filament\Resources\Manufacturers\ManufacturerResource::getUrl(),'02'],
['Подбор по свойствам','Фильтры','Настройте свойства и значения, по которым покупатель сужает выбор.',\App\Models\FilterGroup::count(),\App\Filament\Resources\FilterGroups\FilterGroupResource::getUrl(),'03']
] as [$caption,$title,$description,$count,$url,$number])
<a class="rp-setup-card" href="{{ $url }}"><span class="rp-eyebrow">{{ $caption }}</span><span class="rp-setup-number">{{ $number }}</span><h2>{{ $title }}</h2><p>{{ $description }}</p><footer><span>Добавлено: {{ $count }}</span><strong>Открыть →</strong></footer></a>
@endforeach
</div>
<div class="rp-setup-note"><strong>С чего начать</strong><p>Создайте категории → добавьте производителей → заполните товары → подключите фильтры. Справочники можно дополнять в любое время.</p></div>
</x-filament-panels::page>
