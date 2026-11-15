@extends('layouts.app')
@section('title', 'Confirm Password')
@section('content')
<h1>Confirm Password</h1>
<form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    <p>
        <label for="password">Password</label><br>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        @error('password') <br><span role="alert">{{ $message }}</span> @enderror
    </p>
    <button type="submit">Confirm Password</button>
</form>

@endsection
