@extends('layouts.app')
@section('title', 'User Details')
@section('content')
    <h1>User Details</h1>
    <dl>
        <dt>Username</dt><dd>{{ $user->username }}</dd>
        <dt>Full Name</dt><dd>{{ $user->full_name }}</dd>
        <dt>Email</dt><dd>{{ $user->email }}</dd>
    </dl>
    <a href="{{ route('users.edit', $user) }}">Edit</a>
    <a href="{{ route('users.index') }}">Back to list</a>
@endsection
