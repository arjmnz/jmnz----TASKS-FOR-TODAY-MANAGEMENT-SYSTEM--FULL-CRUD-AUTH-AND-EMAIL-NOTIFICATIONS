<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Task Completed</title></head>
<body>
    <h1>Task Completed</h1>
    <p>A task in {{ config('app.name') }} has changed to done.</p>
    <dl>
        <dt>Task ID</dt><dd>{{ $task->id }}</dd>
        <dt>Title</dt><dd>{{ $task->title }}</dd>
        <dt>Status</dt><dd>{{ $task->status }}</dd>
        <dt>Task Date</dt><dd>{{ $task->task_date->format('Y-m-d') }}</dd>
    </dl>
    <p><a href="{{ route('tasks.show', $task) }}">View task</a></p>
</body>
</html>
