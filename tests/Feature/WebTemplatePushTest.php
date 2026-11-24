<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebTemplate;
use App\Models\WebTemplatePush;
use App\Services\GitHub\TemplatePusher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class WebTemplatePushTest extends TestCase
{
    use RefreshDatabase;

    private function developer(): User
    {
        return User::create([
            'full_name' => 'Template Dev',
            'email' => 'web-template-dev-'.uniqid().'@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_DEVELOPER,
        ]);
    }

    public function test_web_template_registry_is_developer_only_and_renders(): void
    {
        $admin = User::create([
            'full_name' => 'Registry Admin',
            'email' => 'registry-admin@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->get('/developer/templates')->assertForbidden();

        $this->actingAs($this->developer())
            ->get('/developer/templates')
            ->assertOk()
            ->assertSee('Web templates')
            ->assertSee('Add web template');
    }

    public function test_developer_can_register_a_template_and_push_it_to_a_client_repository(): void
    {
        config([
            'services.github.token' => 'gh_test_token',
            'services.github.api_url' => 'https://api.github.test',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_ends_with($url, '/user')) {
                return Http::response(['login' => 'client-owner']);
            }

            if (str_ends_with($url, '/user/repos')) {
                return Http::response(['html_url' => 'https://github.com/client-owner/client-mall'], 201);
            }

            return Http::response(['message' => 'not found'], 404);
        });

        $pusher = new class implements TemplatePusher {
            public array $calls = [];

            public function push(WebTemplate $template, string $remoteUrl, string $branch): void
            {
                $this->calls[] = compact('remoteUrl', 'branch');
            }
        };
        $this->app->instance(TemplatePusher::class, $pusher);

        $developer = $this->developer();
        $this->actingAs($developer);

        $this->post('/developer/templates', [
            'name' => 'Client Mall Template',
            'slug' => 'client-mall-template',
            'description' => 'Shop-first Datatem template.',
            'source_path' => base_path(),
            'source_branch' => 'main',
            'version' => '1.4.0',
        ])->assertRedirect();

        $template = WebTemplate::query()->firstOrFail();

        $this->post('/developer/templates/'.$template->id.'/push', [
            'owner' => 'client-owner',
            'repository' => 'client-mall',
            'branch' => 'main',
            'private' => 1,
            'message' => 'Agreement AGR-123',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $push = WebTemplatePush::query()->firstOrFail();

        $this->assertSame('success', $push->status);
        $this->assertSame('https://github.com/client-owner/client-mall', $push->repo_url);
        $this->assertNotNull($template->fresh()->last_pushed_at);

        $this->assertCount(1, $pusher->calls);
        $this->assertStringContainsString('x-access-token:gh_test_token@github.com/client-owner/client-mall.git', $pusher->calls[0]['remoteUrl']);
        $this->assertSame('main', $pusher->calls[0]['branch']);
    }

    public function test_push_requires_a_github_token(): void
    {
        config(['services.github.token' => '']);

        $developer = $this->developer();
        $this->actingAs($developer);

        $template = WebTemplate::create([
            'name' => 'No Token Template',
            'slug' => 'no-token-template',
            'source_path' => base_path(),
            'source_branch' => 'main',
            'version' => '1.0.0',
            'created_by' => $developer->id,
        ]);

        $this->post('/developer/templates/'.$template->id.'/push', [
            'owner' => 'client',
            'repository' => 'repo',
            'branch' => 'main',
        ])->assertSessionHasErrors('owner');

        $this->assertSame(0, WebTemplatePush::query()->count());
    }

    public function test_failed_push_is_recorded_without_leaking_the_token(): void
    {
        config([
            'services.github.token' => 'gh_secret_token',
            'services.github.api_url' => 'https://api.github.test',
        ]);

        Http::fake([
            'https://api.github.test/user' => Http::response(['login' => 'client']),
            'https://api.github.test/user/repos' => Http::response(['html_url' => 'https://github.com/client/repo'], 201),
        ]);

        $this->app->instance(TemplatePusher::class, new class implements TemplatePusher {
            public function push(WebTemplate $template, string $remoteUrl, string $branch): void
            {
                throw new RuntimeException('fatal: unable to access https://x-access-token:gh_secret_token@github.com/client/repo.git');
            }
        });

        $developer = $this->developer();
        $this->actingAs($developer);

        $template = WebTemplate::create([
            'name' => 'Failing Template',
            'slug' => 'failing-template',
            'source_path' => base_path(),
            'source_branch' => 'main',
            'version' => '1.0.0',
            'created_by' => $developer->id,
        ]);

        $this->post('/developer/templates/'.$template->id.'/push', [
            'owner' => 'client',
            'repository' => 'repo',
            'branch' => 'main',
        ])->assertSessionHasErrors('repository');

        $push = WebTemplatePush::query()->firstOrFail();

        $this->assertSame('failed', $push->status);
        $this->assertStringNotContainsString('gh_secret_token', (string) $push->message);
        $this->assertStringContainsString('x-access-token:***', (string) $push->message);
    }
}
