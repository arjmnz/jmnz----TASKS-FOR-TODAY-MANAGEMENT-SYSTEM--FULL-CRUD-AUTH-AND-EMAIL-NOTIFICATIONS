@extends('layouts.app')
@section('title', $task->exists ? 'Edit Task' : 'Create Task')
@section('content')
    <h1>{{ $task->exists ? 'Edit Task' : 'Create Task' }}</h1>
    @if ($errors->any())
        <p role="alert">Please correct the errors below.</p>
        <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    @endif
    <form method="POST" action="{{ $task->exists ? route('tasks.update', $task) : route('tasks.store') }}">
        @csrf
        @if ($task->exists) @method('PUT') @endif
        <p>
            <label for="title">Title (required)</label><br>
            <input id="title" name="title" type="text" maxlength="255" value="{{ old('title', $task->title) }}" required>
            @error('title') <br><span role="alert">{{ $message }}</span> @enderror
        </p>
        <p>
            <label for="status">Status (required)</label><br>
            <select id="status" name="status" required>
                @foreach (['pending' => 'Pending', 'ongoing' => 'Ongoing', 'done' => 'Done', 'finished' => 'Finished (existing status)'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $task->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <br><span role="alert">{{ $message }}</span> @enderror
        </p>
        <p>
            <label for="task_date">Task Date (required)</label><br>
            <input id="task_date" name="task_date" type="date" value="{{ old('task_date', $task->task_date?->format('Y-m-d')) }}" required>
            @error('task_date') <br><span role="alert">{{ $message }}</span> @enderror
        </p>
        <button type="submit">{{ $task->exists ? 'Save Changes' : 'Create Task' }}</button>
        <a href="{{ route('tasks.index') }}">Cancel</a>
    </form>
@endsection
