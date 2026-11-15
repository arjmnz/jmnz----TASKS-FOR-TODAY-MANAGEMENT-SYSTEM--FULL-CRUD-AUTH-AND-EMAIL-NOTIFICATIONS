@extends('layouts.app')
@section('title', 'Email Password Reset Link')
@section('content')
<h1>Email Password Reset Link</h1>
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <p>
        <label for="email">Email</label><br>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username">
        @error('email') <br><span role="alert">{{ $message }}</span> @enderror
    </p>
    <button type="submit">Email Password Reset Link</button>
</form>

@endsection
