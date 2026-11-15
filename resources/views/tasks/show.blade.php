@extends('layouts.app')
@section('title', 'Task Details')
@section('content')
    <h1>Task Details</h1>
    <dl>
        <dt>Task ID</dt><dd>{{ $task->id }}</dd>
        <dt>Title</dt><dd>{{ $task->title }}</dd>
        <dt>Status</dt><dd>{{ ucfirst($task->status) }}</dd>
        <dt>Task Date</dt><dd>{{ $task->task_date->format('Y-m-d') }}</dd>
    </dl>
    @auth
        <a href="{{ route('tasks.edit', $task) }}">Edit</a>
        <form method="POST" action="{{ route('tasks.destroy', $task) }}">
            @csrf
            @method('DELETE')
            <button type="submit">Archive Task</button>
        </form>
    @endauth
    <p><a href="{{ route('tasks.index') }}">Back to Task List</a></p>
@endsection
