@extends('layouts.app')
@section('title', 'Reset Password')
@section('content')
<h1>Reset Password</h1>
<form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
@error('token') <p role="alert">{{ $message }}</p> @enderror
<p>
        <label for="email">Email</label><br>
        <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required autocomplete="username">
        @error('email') <br><span role="alert">{{ $message }}</span> @enderror
    </p><p>
        <label for="password">Password (minimum 8 characters)</label><br>
        <input id="password" name="password" type="password" required autocomplete="new-password">
        @error('password') <br><span role="alert">{{ $message }}</span> @enderror
    </p><p>
        <label for="password_confirmation">Confirm Password</label><br>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        @error('password_confirmation') <br><span role="alert">{{ $message }}</span> @enderror
    </p>
    <button type="submit">Reset Password</button>
</form>

@endsection
