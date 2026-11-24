<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateDeveloperAccount extends Command
{
    protected $signature = 'template:dev-account
        {email? : Email for the developer account}
        {--password= : Password (generated when omitted)}
        {--name=Template Developer : Display name}
        {--phone= : Phone number (generated when omitted)}';

    protected $description = 'Create or rotate the developer account used to issue template licenses.';

    public function handle(): int
    {
        $email = trim((string) ($this->argument('email') ?: env('SEED_DEVELOPER_EMAIL', '')));

        if ($email === '') {
            $this->error('Provide an email: php artisan template:dev-account dev@example.com --password=secret');

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: Str::password(16));

        $user = User::firstOrNew(['email' => $email]);
        $user->fill([
            'full_name' => (string) $this->option('name'),
            'password' => Hash::make($password),
            'role' => User::ROLE_DEVELOPER,
        ]);

        if (! $user->exists && ! $user->phone) {
            $phone = trim((string) $this->option('phone'));

            if ($phone === '') {
                do {
                    $phone = '05'.random_int(100000000, 999999999);
                } while (User::query()->where('phone', $phone)->exists());
            }

            $user->phone = $phone;
        }

        $user->save();

        $this->info('Developer account ready.');
        $this->line('Email: '.$user->email);
        $this->line('Password: '.$password);
        $this->line('Console: '.url('/developer'));
        $this->warn('Store this password securely and change it after the first login.');

        return self::SUCCESS;
    }
}
