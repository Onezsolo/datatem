<?php

declare(strict_types=1);

namespace App\Services\GitHub;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GitHubService
{
    public function configured(): bool
    {
        return $this->token() !== '';
    }

    public function login(): string
    {
        $response = $this->request()->get('/user');

        if (! $response->successful()) {
            throw new RuntimeException('Could not read the GitHub account: '.$response->body());
        }

        return (string) $response->json('login');
    }

    /**
     * Create the repository when missing and return its HTTPS URL.
     */
    public function ensureRepository(string $owner, string $repository, bool $private, string $description = ''): string
    {
        $ownerIsUser = strcasecmp($owner, $this->login()) === 0;
        $endpoint = $ownerIsUser ? '/user/repos' : '/orgs/'.$owner.'/repos';

        $response = $this->request()->post($endpoint, [
            'name' => $repository,
            'private' => $private,
            'description' => $description,
            'auto_init' => false,
        ]);

        if ($response->successful()) {
            return (string) $response->json('html_url');
        }

        if ($response->status() === 422) {
            $existing = $this->request()->get('/repos/'.$owner.'/'.$repository);

            if ($existing->successful()) {
                return (string) $existing->json('html_url');
            }
        }

        throw new RuntimeException('GitHub rejected the repository: '.($response->json('message') ?? $response->body()));
    }

    public function authenticatedRemoteUrl(string $owner, string $repository): string
    {
        return 'https://x-access-token:'.$this->token().'@github.com/'.$owner.'/'.$repository.'.git';
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.github.api_url', 'https://api.github.com'), '/'))
            ->acceptJson()
            ->asJson()
            ->withToken($this->token())
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28'])
            ->timeout(30);
    }

    public function token(): string
    {
        return trim((string) config('services.github.token'));
    }
}
