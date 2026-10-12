<?php

namespace Tests\Feature;

use App\Actions\Content\SafeUrl;
use App\Filament\Pages\Content;
use App\Filament\Pages\SiteContent;
use App\Models\Banner;
use App\Models\ContentPage;
use App\Models\MenuLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ContentWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_scope_hides_drafts_and_future_content(): void
    {
        foreach ([['draft', null], ['published', now()->addDay()], ['published', now()->subDay()]] as $i => $values) {
            ContentPage::create(['title' => 'Page '.$i, 'slug' => 'page-'.$i, 'kind' => 'article', 'body' => 'Text', 'status' => $values[0], 'published_at' => $values[1]]);
        }
        $this->assertSame(['page-2'], ContentPage::published()->pluck('slug')->all());
    }

    public function test_unsafe_menu_urls_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        MenuLink::create(['label' => 'Bad', 'url' => 'javascript:alert(1)', 'location' => 'header']);
    }

    public function test_guests_cannot_access_content_admin(): void
    {
        $this->assertFalse(Content::canAccess());
    }

    public function test_public_content_is_escaped_and_drafts_return_404(): void
    {
        $page = ContentPage::create(['kind' => 'article', 'title' => 'Growing', 'slug' => 'growing', 'body' => '<script>alert(1)</script>', 'status' => 'published', 'published_at' => now()]);
        $this->get('/articles/growing')->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $page->update(['status' => 'draft']);
        $this->get('/articles/growing')->assertNotFound();
        $this->get('/articles')->assertOk()->assertDontSee('Growing');
    }

    public function test_admin_can_publish_and_change_home_content(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(Content::class)->call('create')->set('form.title', 'Семена')->set('form.body', 'Описание')->set('form.status', 'published')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('content_pages', ['slug' => 'semena', 'status' => 'published']);
        Livewire::test(SiteContent::class)->set('banner.title', 'My banner')->set('banner.url', '/catalog')->call('saveBanner')->assertHasNoErrors();
        $this->get('/')->assertOk()->assertSee('My banner');
    }

    public function test_customer_and_blocked_admin_cannot_mutate_content(): void
    {
        foreach ([User::factory()->create(), User::factory()->admin()->blocked()->create()] as $user) {
            $this->actingAs($user);
            $this->assertFalse(Content::canAccess());
            try {
                (new Content)->delete(1);
                $this->fail('Must deny');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }

    public function test_menu_suppresses_links_to_unpublished_pages(): void
    {
        ContentPage::create(['kind' => 'page', 'title' => 'Draft', 'slug' => 'draft', 'body' => 'text', 'status' => 'draft']);
        MenuLink::create(['label' => 'Draft', 'url' => '/pages/draft', 'location' => 'header']);
        $this->assertCount(0, MenuLink::publicLinks('header'));
        foreach (['//evil.test', 'data:text/html,bad', '/\\evil.test', 'javaSCRIPT:bad'] as $url) {
            $this->assertFalse(SafeUrl::valid($url));
        }
    }

    public function test_admin_can_upload_banner_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(SiteContent::class)
            ->set('banner.title', 'Upload')
            ->set('bannerUpload', UploadedFile::fake()->image('banner.jpg'))
            ->call('saveBanner')->assertHasNoErrors()->assertSet('bannerUpload', null);
        $banner = Banner::where('title', 'Upload')->firstOrFail();
        $this->assertStringStartsWith('/storage/banners/', $banner->image);
        Storage::disk('public')->assertExists(substr($banner->image, strlen('/storage/')));
    }
}
