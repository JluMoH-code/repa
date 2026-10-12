<?php

namespace App\Filament\Pages;

use App\Models\ContentPage;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Content extends Page
{
    protected string $view = 'filament.pages.content';

    protected static ?string $navigationLabel = 'Статьи и страницы';

    protected static string|\UnitEnum|null $navigationGroup = 'Сайт';

    protected static ?string $title = 'Статьи и страницы';

    public string $search = '';

    public ?int $editingId = null;

    public bool $editorOpen = false;

    public array $form = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true && ! auth()->user()->is_blocked;
    }

    private function authorizeAction(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function create(): void
    {
        $this->authorizeAction();
        $this->editingId = null;
        $this->resetValidation();
        $this->form = ['kind' => 'article', 'title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'status' => 'draft', 'published_at' => '', 'seo_title' => '', 'seo_description' => ''];
        $this->editorOpen = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeAction();
        $p = ContentPage::findOrFail($id);
        $this->editingId = $id;
        $this->form = $p->only(['kind', 'title', 'slug', 'excerpt', 'body', 'status', 'seo_title', 'seo_description']);
        $this->form['published_at'] = $p->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->resetValidation();
        $this->editorOpen = true;
    }

    public function save(): void
    {
        $this->authorizeAction();
        if (empty($this->form['slug'])) {
            $this->form['slug'] = Str::slug($this->form['title'] ?? '');
        }
        $data = $this->validate(['form.kind' => ['required', Rule::in(['article', 'page'])], 'form.title' => 'required|string|max:255', 'form.slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('content_pages', 'slug')->ignore($this->editingId)], 'form.excerpt' => 'nullable|string|max:2000', 'form.body' => 'required|string|max:100000', 'form.status' => ['required', Rule::in(['draft', 'published'])], 'form.published_at' => 'nullable|date', 'form.seo_title' => 'nullable|string|max:255', 'form.seo_description' => 'nullable|string|max:1000'])['form'];
        $data['published_at'] = $data['published_at'] ?: ($data['status'] === 'published' ? now() : null);
        if ($this->editingId) {
            ContentPage::findOrFail($this->editingId)->update($data);
        } else {
            ContentPage::create($data);
        }
        $this->editorOpen = false;
        Notification::make()->title('Сохранено')->success()->send();
    }

    public function delete(int $id): void
    {
        $this->authorizeAction();
        ContentPage::findOrFail($id)->delete();
        $this->editorOpen = false;
    }

    protected function getViewData(): array
    {
        return ['pages' => ContentPage::query()->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))->latest()->get()];
    }
}
