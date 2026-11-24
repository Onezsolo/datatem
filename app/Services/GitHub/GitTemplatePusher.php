<?php

declare(strict_types=1);

namespace App\Services\GitHub;

use App\Models\WebTemplate;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

final class GitTemplatePusher implements TemplatePusher
{
    public function push(WebTemplate $template, string $remoteUrl, string $branch): void
    {
        $source = $template->sourceReference();

        if ($source === '') {
            throw new RuntimeException('Template has no source path or git URL.');
        }

        $temp = storage_path('app/template-push/'.Str::uuid());

        try {
            $clone = ['git', 'clone', '--quiet'];

            if ($template->source_branch) {
                $clone[] = '--branch';
                $clone[] = $template->source_branch;
            }

            $clone[] = $source;
            $clone[] = $temp;

            $this->run($clone);
            $this->run(['git', '-C', $temp, 'push', $remoteUrl, 'HEAD:refs/heads/'.$branch]);
        } finally {
            File::deleteDirectory($temp);
        }
    }

    /**
     * @param  array<int, string>  $command
     */
    private function run(array $command): void
    {
        $process = new Process($command, null, [
            'GIT_TERMINAL_PROMPT' => '0',
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Git failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }
}
