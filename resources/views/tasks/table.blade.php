<table>
    <thead>
        <tr>
            <th scope="col">Task ID</th>
            <th scope="col">Title</th>
            <th scope="col">Status</th>
            <th scope="col">Task Date</th>
            <th scope="col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($tasks as $task)
            <tr>
                <td>{{ $task->id }}</td>
                <td>{{ $task->title }}</td>
                <td>{{ ucfirst($task->status) }}</td>
                <td>{{ $task->task_date->format('Y-m-d') }}</td>
                <td>
                    <a href="{{ route('tasks.show', $task) }}">View</a>
                    @auth
                        <a href="{{ route('tasks.edit', $task) }}">Edit</a>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Archive</button>
                        </form>
                    @endauth
                </td>
            </tr>
        @empty
            <tr><td colspan="5">{{ $emptyMessage }}</td></tr>
        @endforelse
    </tbody>
</table>
