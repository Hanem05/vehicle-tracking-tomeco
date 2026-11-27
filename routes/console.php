<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:create-admin {email}', function (string $email) {
    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
        $this->error('Use a valid email address that is not already registered.');
        return 1;
    }
    $name = $this->ask('Name');
    $password = $this->secret('Password (at least 12 characters)');
    if (! $name || strlen((string) $password) < 12) {
        $this->error('Name and a password of at least 12 characters are required.');
        return 1;
    }
    User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => 'admin', 'is_active' => true]);
    $this->info('Admin created.');
    return 0;
})->purpose('Create the first administrator interactively');

Artisan::command('app:bootstrap-admin {email=admin@local.test}', function (string $email) {
    if (User::where('role', 'admin')->exists()) {
        $this->error('An administrator already exists. Use the accounts screen or app:create-admin.');
        return 1;
    }
    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::where('email', $email)->exists()) {
        $this->error('Use an unused, valid email address.');
        return 1;
    }
    $password = Str::random(20);
    User::create(['name' => 'System Administrator', 'email' => $email, 'password' => $password, 'role' => 'admin', 'is_active' => true]);
    $this->info('Admin email: '.$email);
    $this->info('Temporary password: '.$password);
    $this->warn('Save this password now and change it after signing in. It will not be shown again.');
    return 0;
})->purpose('Create the first administrator with a random temporary password');

Artisan::command('app:reset-admin-password {email=admin@local.test}', function (string $email) {
    $user = User::where('email', $email)->where('role', 'admin')->first();
    if (! $user) {
        $this->error('Admin account not found.');
        return 1;
    }
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $groups = [];
    for ($group = 0; $group < 4; $group++) {
        $piece = '';
        for ($i = 0; $i < 4; $i++) $piece .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        $groups[] = $piece;
    }
    $password = implode('-', $groups);
    $user->update(['password' => $password]);
    $this->info('Admin email: '.$email);
    $this->info('New temporary password: '.$password);
    $this->warn('Save this password now and change it after signing in.');
    return 0;
})->purpose('Reset an admin password to an easy-to-read random temporary password');

Artisan::command('app:sync-dev-admins', function () {
    if (! app()->environment('local')) {
        $this->error('Developer accounts are only available in the local environment.');
        return 1;
    }

    $admins = [];
    foreach ([1, 2] as $number) {
        $email = trim((string) env("DEV_ADMIN_{$number}_EMAIL"));
        $name = trim((string) env("DEV_ADMIN_{$number}_NAME"));
        $password = (string) env("DEV_ADMIN_{$number}_PASSWORD");
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || strlen($password) < 20) {
            $this->error("Developer admin {$number} needs a valid email, name, and password of at least 20 characters.");
            return 1;
        }
        $admins[] = compact('email', 'name', 'password');
    }
    if ($admins[0]['email'] === $admins[1]['email'] || $admins[0]['password'] === $admins[1]['password']) {
        $this->error('Developer admin emails and passwords must be distinct.');
        return 1;
    }

    foreach ($admins as $admin) {
        $existing = User::where('email', $admin['email'])->first();
        if ($existing && $existing->role !== 'admin') {
            $this->error('A configured email already belongs to a non-admin account.');
            return 1;
        }
    }

    DB::transaction(function () use ($admins) {
        foreach ($admins as $admin) {
            $user = User::where('email', $admin['email'])->first();
            if (! $user) {
                User::create(['name' => $admin['name'], 'email' => $admin['email'], 'password' => $admin['password'], 'role' => 'admin', 'is_active' => true]);
                continue;
            }
            $changes = ['name' => $admin['name'], 'role' => 'admin', 'is_active' => true];
            if (! Hash::check($admin['password'], $user->password)) $changes['password'] = $admin['password'];
            $user->update($changes);
        }
    });
    $this->info('Configured 2 developer admin accounts.');
    return 0;
})->purpose('Sync two local admin accounts from environment variables without printing passwords');
