<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Mail\TaskCompleted;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function today(): View
    {
        $today = today();
        $tasks = Task::active()->whereDate('task_date', $today->toDateString())->orderBy('id')->get();
        return view('home', compact('tasks', 'today'));
    }

    public function index(): View
    {
        $tasks = Task::active()->orderBy('task_date')->orderBy('id')->get();
        return view('tasks.index', compact('tasks'));
    }

    public function create(): View
    {
        return view('tasks.form', ['task' => new Task(['status' => 'pending', 'task_date' => today()])]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        // The assessment triggers completion mail on a status change in update.
        Task::create($request->validated());
        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    public function show(Task $task): View
    {
        abort_if($task->is_archived, 404);
        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task): View
    {
        abort_if($task->is_archived, 404);
        return view('tasks.form', compact('task'));
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();
        $completed = DB::transaction(function () use ($task, $data): bool {
            // Reload under a lock so a second request sees the saved status.
            $current = Task::active()->lockForUpdate()->findOrFail($task->id);
            $justCompleted = $current->status !== 'done' && $data['status'] === 'done';
            if ($justCompleted) {
                Validator::make(
                    ['recipient' => config('tasks.completion_recipient')],
                    ['recipient' => ['required', 'email']]
                )->validate();
            }
            $current->update($data);
            if ($justCompleted) {
                // Synchronous log/test SMTP delivery needs no queue worker.
                // An exception rolls back the update so it can be retried.
                Mail::to(config('tasks.completion_recipient'))->send(new TaskCompleted($current));
            }
            return $justCompleted;
        });
        return redirect()->route('tasks.index')->with('success', $completed
            ? 'Task updated successfully. Completion notification generated.'
            : 'Task updated successfully.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $updated = Task::active()->whereKey($task->id)->update(['is_archived' => true]);
        abort_unless($updated, 404);
        return redirect()->route('tasks.index')->with('success', 'Task archived successfully.');
    }
}
