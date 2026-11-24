<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WebTemplate;
use App\Models\WebTemplatePush;
use App\Services\GitHub\GitHubService;
use App\Services\GitHub\TemplatePusher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class DeveloperTemplateController extends Controller
{
    public function index(): View
    {
        return view('developer.templates', [
            'templates' => WebTemplate::query()->latest('id')->paginate(20),
            'pushes' => WebTemplatePush::query()->latest('id')->limit(10)->get(),
            'githubReady' => app(GitHubService::class)->configured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('web_templates', 'slug')],
            'description' => ['nullable', 'string', 'max:2000'],
            'source_path' => ['nullable', 'string', 'max:500'],
            'source_git_url' => ['nullable', 'url', 'max:500'],
            'source_branch' => ['nullable', 'string', 'max:120'],
            'version' => ['nullable', 'string', 'max:40'],
        ]);

        if (empty($data['source_path']) && empty($data['source_git_url'])) {
            return back()->withInput()->withErrors(['source_path' => 'Provide a local source path or a git URL.']);
        }

        if (! empty($data['source_path']) && ! is_dir($data['source_path'])) {
            return back()->withInput()->withErrors(['source_path' => 'Source path does not exist on this server.']);
        }

        WebTemplate::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'source_path' => $data['source_path'] ?? null,
            'source_git_url' => $data['source_git_url'] ?? null,
            'source_branch' => $data['source_branch'] ?? 'main',
            'version' => $data['version'] ?? '1.0.0',
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Web template added.');
    }

    public function push(Request $request, WebTemplate $template, GitHubService $github, TemplatePusher $pusher): RedirectResponse
    {
        $data = $request->validate([
            'owner' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'repository' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.-]+$/'],
            'branch' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'private' => ['nullable', 'boolean'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        if (! $github->configured()) {
            return back()->withErrors(['owner' => 'Set GITHUB_TOKEN before pushing to GitHub.']);
        }

        $push = WebTemplatePush::create([
            'web_template_id' => $template->id,
            'pushed_by' => $request->user()->id,
            'owner' => $data['owner'],
            'repository' => $data['repository'],
            'branch' => $data['branch'],
            'status' => 'pending',
            'message' => $data['message'] ?? null,
        ]);

        try {
            $repoUrl = $github->ensureRepository(
                $data['owner'],
                $data['repository'],
                (bool) ($data['private'] ?? false),
                (string) ($template->description ?? '')
            );

            $pusher->push(
                $template,
                $github->authenticatedRemoteUrl($data['owner'], $data['repository']),
                $data['branch']
            );

            $push->update(['status' => 'success', 'repo_url' => $repoUrl]);
            $template->update(['last_pushed_at' => now()]);

            return back()->with('status', 'Pushed '.$template->name.' to '.$repoUrl);
        } catch (Throwable $exception) {
            $message = preg_replace('/https:\/\/x-access-token:[^@]+@/', 'https://x-access-token:***@', $exception->getMessage()) ?? $exception->getMessage();
            $message = str_replace($github->token(), '***', $message);

            $push->update(['status' => 'failed', 'message' => substr($message, 0, 1500)]);

            return back()->withErrors(['repository' => 'Push failed: '.substr($message, 0, 300)]);
        }
    }

    public function destroy(WebTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('status', 'Web template removed.');
    }
}
