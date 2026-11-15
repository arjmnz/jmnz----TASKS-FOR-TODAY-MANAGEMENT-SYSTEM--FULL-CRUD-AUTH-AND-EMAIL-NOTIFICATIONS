<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Artisan::command('tfa8:set-password {username=arjmnz}', function () {
    $user = \App\Models\User::where('username', $this->argument('username'))->first();
    if (! $user) {
        $this->error('User not found.');
        return 1;
    }
    $password = $this->secret('Enter a new password (at least 8 characters)');
    $confirmation = $this->secret('Confirm the new password');
    $validator = \Illuminate\Support\Facades\Validator::make(
        ['password' => $password, 'password_confirmation' => $confirmation],
        ['password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()]]
    );
    if ($validator->fails()) {
        $this->error($validator->errors()->first());
        return 1;
    }
    $user->forceFill(['password' => $password, 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
    $this->info('Password saved for '.$user->username.'.');
})->purpose('Set a password for an existing TFA7 account without changing its information');
