@extends('layouts.app')

@section('title', 'Task List - Tasks for Today')

@section('content')
    <h1>Task List</h1>
    <p>All tasks, ordered by date from earliest to latest.</p>
    @auth
        <p><a href="{{ route('tasks.create') }}">Create Task</a></p>
    @else
        <p><a href="{{ route('login') }}">Log in</a> to manage tasks.</p>
    @endauth
    @include('tasks.table', ['emptyMessage' => 'No tasks found.'])
@endsection
