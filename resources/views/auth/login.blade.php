@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<h1>Log in</h1>
<form method="POST" action="{{ route('login') }}">
    @csrf
    <p>
        <label for="email">Email</label><br>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
        @error('email') <br><span role="alert">{{ $message }}</span> @enderror
    </p><p>
        <label for="password">Password</label><br>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        @error('password') <br><span role="alert">{{ $message }}</span> @enderror
    </p><p><label><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Remember me</label></p>
    <button type="submit">Log in</button>
</form>
<p><a href="{{ route('password.request') }}">Forgot your password?</a></p>
<p><a href="{{ route('register') }}">Register a new account</a></p>
@endsection
