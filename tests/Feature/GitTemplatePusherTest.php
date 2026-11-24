<?php

namespace Tests\Feature;

use App\Models\WebTemplate;
use App\Services\GitHub\GitTemplatePusher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class GitTemplatePusherTest extends TestCase
{
    public function test_pusher_clones_a_local_source_and_pushes_to_a_bare_remote(): void
    {
        $base = storage_path('app/pusher-test-'.Str::random(8));
        $source = $base.DIRECTORY_SEPARATOR.'source';
        $remote = $base.DIRECTORY_SEPARATOR.'remote.git';

        File::ensureDirectoryExists($source);

        try {
            $this->runProcess(['git', 'init', '-b', 'main', $source]);
            $this->runProcess(['git', '-C', $source, 'config', 'user.email', 'tests@example.com']);
            $this->runProcess(['git', '-C', $source, 'config', 'user.name', 'Tests']);
            File::put($source.DIRECTORY_SEPARATOR.'README.md', "# Template\n");
            $this->runProcess(['git', '-C', $source, 'add', '.']);
            $this->runProcess(['git', '-C', $source, 'commit', '-m', 'initial']);

            $this->runProcess(['git', 'init', '--bare', $remote]);

            $template = new WebTemplate([
                'name' => 'Local Template',
                'slug' => 'local-template',
                'source_path' => $source,
                'source_branch' => 'main',
            ]);

            (new GitTemplatePusher())->push($template, $remote, 'main');

            $log = new Process(['git', '-C', $remote, 'log', '--oneline', 'main']);
            $log->run();

            $this->assertTrue($log->isSuccessful());
            $this->assertStringContainsString('initial', $log->getOutput());
        } finally {
            File::deleteDirectory($base);
        }
    }

    /**
     * @param  array<int, string>  $command
     */
    private function runProcess(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
    }
}
