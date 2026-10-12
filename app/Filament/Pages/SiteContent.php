<?php

namespace App\Filament\Pages;

use App\Actions\Content\SafeUrl;
use App\Actions\Settings\SettingsManager;
use App\Models\Banner;
use App\Models\MenuLink;
use App\Models\Product;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

class SiteContent extends Page
{
    use WithFileUploads;

    public $bannerUpload = null;

    protected string $view = 'filament.pages.site-content';

    protected static ?string $navigationLabel = 'Главная и меню';

    protected static string|\UnitEnum|null $navigationGroup = 'Сайт';

    protected static ?string $title = 'Главная и меню';

    public array $banner = ['title' => '', 'subtitle' => '', 'image' => '', 'url' => '', 'is_active' => true, 'sort_order' => 0];

    public array $link = ['label' => '', 'url' => '', 'location' => 'header', 'is_active' => true, 'sort_order' => 0];

    public ?int $bannerId = null;

    public ?int $linkId = null;

    public string $intro = '';

    public string $productSearch = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true && ! auth()->user()->is_blocked;
    }

    private function authorizeAction(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function mount(): void
    {
        $this->authorizeAction();
        $this->intro = app(SettingsManager::class)->get('home_intro', '') ?? '';
    }

    public function saveIntro(): void
    {
        $this->authorizeAction();
        $this->validate(['intro' => 'nullable|string|max:3000']);
        app(SettingsManager::class)->set('home_intro', $this->intro);
        $this->saved();
    }

    public function editBanner(int $id): void
    {
        $this->authorizeAction();
        $this->bannerUpload = null;
        $this->bannerId = $id;
        $this->banner = Banner::findOrFail($id)->only(['title', 'subtitle', 'image', 'url', 'is_active', 'sort_order']);
        $this->resetValidation();
    }

    public function saveBanner(): void
    {
        $this->authorizeAction();
        $this->validate(['bannerUpload' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120']]);
        $data = $this->validate(['banner.title' => 'required|string|max:255', 'banner.subtitle' => 'nullable|string|max:1000', 'banner.image' => ['nullable', 'string', 'max:255', SafeUrl::rule()], 'banner.url' => ['nullable', 'string', 'max:255', SafeUrl::rule()], 'banner.is_active' => 'boolean', 'banner.sort_order' => 'required|integer|min:0|max:10000'])['banner'];
        if ($this->bannerUpload) {
            $data['image'] = '/storage/'.$this->bannerUpload->store('banners', 'public');
        }
        if ($this->bannerId) {
            Banner::findOrFail($this->bannerId)->update($data);
        } else {
            Banner::create($data);
        }
        $this->bannerUpload = null;
        $this->bannerId = null;
        $this->banner = ['title' => '', 'subtitle' => '', 'image' => '', 'url' => '', 'is_active' => true, 'sort_order' => 0];
        $this->saved();
    }

    public function deleteBanner(int $id): void
    {
        $this->authorizeAction();
        Banner::findOrFail($id)->delete();
        $this->bannerUpload = null;
        $this->bannerId = null;
    }

    public function editLink(int $id): void
    {
        $this->authorizeAction();
        $this->linkId = $id;
        $this->link = MenuLink::findOrFail($id)->only(['label', 'url', 'location', 'is_active', 'sort_order']);
        $this->resetValidation();
    }

    public function saveLink(): void
    {
        $this->authorizeAction();
        $data = $this->validate(['link.label' => 'required|string|max:255', 'link.url' => ['required', 'string', 'max:255', SafeUrl::rule()], 'link.location' => ['required', Rule::in(['header', 'footer'])], 'link.is_active' => 'boolean', 'link.sort_order' => 'required|integer|min:0|max:10000'])['link'];
        if ($this->linkId) {
            MenuLink::findOrFail($this->linkId)->update($data);
        } else {
            MenuLink::create($data);
        }
        $this->linkId = null;
        $this->link = ['label' => '', 'url' => '', 'location' => 'header', 'is_active' => true, 'sort_order' => 0];
        $this->saved();
    }

    public function deleteLink(int $id): void
    {
        $this->authorizeAction();
        MenuLink::findOrFail($id)->delete();
        $this->linkId = null;
    }

    public function toggleCollection(int $id, string $flag): void
    {
        $this->authorizeAction();
        abort_unless(in_array($flag, ['is_bestseller', 'is_recommended'], true), 422);
        $p = Product::findOrFail($id);
        $p->update([$flag => ! $p->$flag]);
    }

    private function saved(): void
    {
        Notification::make()->title('Сохранено')->success()->send();
    }

    protected function getViewData(): array
    {
        return ['banners' => Banner::orderBy('sort_order')->get(), 'links' => MenuLink::orderBy('location')->orderBy('sort_order')->get(), 'products' => Product::query()->when($this->productSearch, fn ($q) => $q->where('name', 'like', '%'.$this->productSearch.'%'))->orderBy('name')->limit(50)->get()];
    }
}
