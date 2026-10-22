<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;

#[Signature('admin:create {email} {name} {--moderator : Admin emas, moderator yaratish}')]
#[Description('Admin panelga kiruvchi admin yoki moderator yaratadi')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $user = User::updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => $this->argument('name'), 'password' => password('Parol', required: true, validate: 'min:10')],
        );

        $role = $this->option('moderator') ? Role::Moderator : Role::Admin;
        $user->assignRole($role->value);

        $this->info("{$role->value} tayyor: {$user->email} → /admin");

        return self::SUCCESS;
    }
}
