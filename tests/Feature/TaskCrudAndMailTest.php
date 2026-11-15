<?php

namespace Tests\Feature;

use App\Mail\TaskCompleted;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TaskCrudAndMailTest extends TestCase
{
    use RefreshDatabase;

    private function task(array $overrides = []): Task
    {
        return Task::create(array_merge([
            'title' => 'Test task', 'status' => 'pending', 'task_date' => today()->toDateString(),
        ], $overrides));
    }

    private function fields(Task $task, array $overrides = []): array
    {
        return array_merge([
            'title' => $task->title, 'status' => $task->status, 'task_date' => $task->task_date->format('Y-m-d'),
        ], $overrides);
    }

    public function test_all_four_required_pages_and_task_details_are_public(): void
    {
        $this->seed();
        foreach (['/', '/tasks', '/profile', '/about'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/profile')->assertSeeText('Arwind Craig R. Jimenez');
        $this->get('/tasks/'.Task::firstOrFail()->id)->assertOk();
        $this->get('/tasks')->assertDontSee('Create Task')->assertDontSee('>Archive<', false);
    }

    public function test_guests_cannot_create_edit_update_or_archive_tasks(): void
    {
        $task = $this->task();
        $this->get('/tasks/create')->assertRedirect('/login');
        $this->get('/tasks/'.$task->id.'/edit')->assertRedirect('/login');
        $this->post('/tasks', $this->fields($task))->assertRedirect('/login');
        $this->put('/tasks/'.$task->id, $this->fields($task, ['status' => 'done']))->assertRedirect('/login');
        $this->patch('/tasks/'.$task->id, $this->fields($task))->assertRedirect('/login');
        $this->delete('/tasks/'.$task->id)->assertRedirect('/login');
        $this->assertSame('pending', $task->fresh()->status);
        $this->assertFalse($task->fresh()->is_archived);
        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_logged_in_user_can_create_and_edit_a_task(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $this->get('/tasks/create')->assertOk();
        $this->post('/tasks', [
            'title' => 'New task', 'status' => 'pending', 'task_date' => '2026-11-15',
            'is_archived' => true,
        ])->assertRedirect('/tasks')->assertSessionHasNoErrors();
        $task = Task::firstOrFail();
        $this->assertFalse($task->is_archived);
        $this->get('/tasks/'.$task->id.'/edit')->assertOk()->assertSee('New task');
        $this->put('/tasks/'.$task->id, $this->fields($task, [
            'title' => 'Updated task', 'status' => 'ongoing',
        ]))->assertRedirect('/tasks')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Updated task', 'status' => 'ongoing']);
        Mail::assertNothingSent();
    }

    public function test_invalid_fields_preserve_input_without_modifying_data_or_sending_mail(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $this->from('/tasks/create')->post('/tasks', [
            'title' => 'Keep this title', 'status' => 'invalid', 'task_date' => '2026-02-30',
        ])->assertRedirect('/tasks/create')->assertSessionHasErrors(['status', 'task_date'])
            ->assertSessionHasInput('title', 'Keep this title');
        $this->get('/tasks/create')->assertSee('Keep this title');
        $this->post('/tasks', [])->assertSessionHasErrors(['title', 'status', 'task_date']);
        $this->post('/tasks', ['title' => str_repeat('x', 256), 'status' => 'pending', 'task_date' => '2026-11-15'])
            ->assertSessionHasErrors('title');
        $this->assertDatabaseCount('tasks', 0);
        $task = $this->task();
        $this->put('/tasks/'.$task->id, $this->fields($task, ['title' => '', 'status' => 'done']))
            ->assertSessionHasErrors('title');
        $this->assertSame('pending', $task->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_archiving_keeps_the_record_but_hides_it_and_blocks_direct_access(): void
    {
        Mail::fake();
        $task = $this->task(['title' => 'Archived task title']);
        $this->actingAs(User::factory()->create());
        $this->delete('/tasks/'.$task->id)->assertRedirect('/tasks');
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'is_archived' => 1]);
        $this->get('/')->assertDontSeeText('Archived task title');
        $this->get('/tasks')->assertDontSeeText('Archived task title');
        $this->get('/tasks/'.$task->id)->assertNotFound();
        $this->get('/tasks/'.$task->id.'/edit')->assertNotFound();
        $this->put('/tasks/'.$task->id, $this->fields($task, ['status' => 'done']))->assertNotFound();
        $this->delete('/tasks/'.$task->id)->assertNotFound();
        $this->assertSame('pending', $task->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_changing_pending_to_done_sends_one_email_and_done_edits_do_not_resend(): void
    {
        Mail::fake();
        config(['tasks.completion_recipient' => 'arjimenez@feualabang.edu.ph']);
        $this->actingAs(User::factory()->create());
        $task = $this->task();
        $this->put('/tasks/'.$task->id, $this->fields($task, ['status' => 'done']))
            ->assertRedirect('/tasks')->assertSessionHasNoErrors();
        $this->assertSame('done', $task->fresh()->status);
        Mail::assertSent(TaskCompleted::class, fn ($mail) =>
            $mail->hasTo('arjimenez@feualabang.edu.ph') && $mail->task->id === $task->id);
        $this->put('/tasks/'.$task->id, $this->fields($task, ['status' => 'done', 'title' => 'Edited done task']))
            ->assertRedirect('/tasks');
        $this->put('/tasks/'.$task->id, $this->fields($task, ['status' => 'done']))->assertRedirect('/tasks');
        Mail::assertSentCount(1);
    }

    public function test_reopening_then_completing_a_task_sends_for_the_new_transition(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $task = $this->task(['status' => 'done']);
        $this->put('/tasks/'.$task->id, $this->fields($task, ['status' => 'pending']))->assertRedirect('/tasks');
        Mail::assertNothingSent();
        $this->put('/tasks/'.$task->id, $this->fields($task, ['status' => 'done']))->assertRedirect('/tasks');
        Mail::assertSentCount(1);
    }

    public function test_invalid_recipient_rolls_back_the_completion(): void
    {
        Mail::fake();
        config(['tasks.completion_recipient' => 'invalid-address']);
        $this->actingAs(User::factory()->create());
        $task = $this->task();
        $this->put('/tasks/'.$task->id, $this->fields($task, ['status' => 'done']))->assertSessionHasErrors('recipient');
        $this->assertSame('pending', $task->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_transport_failure_does_not_leave_the_task_marked_done(): void
    {
        $this->actingAs(User::factory()->create());
        $task = $this->task();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Test mail failure'));
        $this->put('/tasks/'.$task->id, $this->fields($task, ['status' => 'done']))->assertServerError();
        $this->assertSame('pending', $task->fresh()->status);
    }

    public function test_the_mailable_renders_the_task_details_and_escapes_html(): void
    {
        $task = $this->task(['title' => '<script>alert(1)</script>', 'status' => 'done']);
        $mail = new TaskCompleted($task);
        $mail->assertSeeInHtml('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $mail->assertSeeInHtml($task->task_date->format('Y-m-d'));
        $mail->assertSeeInHtml('done');
    }

    public function test_reseeding_preserves_changed_status_and_archived_flags(): void
    {
        $this->seed();
        $task = Task::where('title', 'do technical 7')->firstOrFail();
        $task->forceFill(['status' => 'done', 'is_archived' => true])->save();
        $this->seed();
        $this->assertSame('done', $task->fresh()->status);
        $this->assertTrue($task->fresh()->is_archived);
        $this->assertDatabaseCount('tasks', 8);
    }
}
