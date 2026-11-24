<?php

declare(strict_types=1);

namespace App\Services\GitHub;

use App\Models\WebTemplate;

interface TemplatePusher
{
    public function push(WebTemplate $template, string $remoteUrl, string $branch): void;
}
