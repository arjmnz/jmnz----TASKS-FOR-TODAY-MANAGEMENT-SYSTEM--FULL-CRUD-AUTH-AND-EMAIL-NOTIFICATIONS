@extends('layouts.app')
@section('title', 'Register')
@section('content')
<h1>Register</h1>
<form method="POST" action="{{ route('register') }}">
    @csrf
    <p>
        <label for="username">Username</label><br>
        <input id="username" name="username" type="text" value="{{ old('username') }}" required autocomplete="username">
        @error('username') <br><span role="alert">{{ $message }}</span> @enderror
    </p><p>
        <label for="full_name">Full Name</label><br>
        <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required autocomplete="name">
        @error('full_name') <br><span role="alert">{{ $message }}</span> @enderror
    </p><p>
        <label for="email">Email</label><br>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
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
    <button type="submit">Register</button>
</form>

@endsection
