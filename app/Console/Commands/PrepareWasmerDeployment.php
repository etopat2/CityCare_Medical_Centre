<?php

namespace App\Console\Commands;

use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WasmerAdminSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

#[Signature('wasmer:prepare')]
#[Description('Run production migrations and provision the initial Wasmer administrator')]
class PrepareWasmerDeployment extends Command
{
    public function handle(): int
    {
        if (blank(config('wasmer.admin_email')) || blank(config('wasmer.admin_password'))) {
            $this->components->error('Set WASMER_ADMIN_EMAIL and WASMER_ADMIN_PASSWORD as Wasmer app secrets before deploying.');

            return self::FAILURE;
        }

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! Role::query()->where('name', 'admin')->where('guard_name', 'web')->exists()) {
            if (Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        if (Artisan::call('db:seed', ['--class' => WasmerAdminSeeder::class, '--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->components->info('Wasmer database is migrated and the initial administrator is ready.');

        return self::SUCCESS;
    }
}
