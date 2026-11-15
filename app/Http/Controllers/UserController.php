<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderBy('id')->get()]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User]);
    }

    public function store(Request $request)
    {
        User::create($this->validatedData($request));

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validatedData($request, $user);
        $oldAvatar = $user->avatar;
        $newAvatar = null;

        if ($request->hasFile('avatar')) {
            try {
                $thumbnail = ImageManager::gd()->read($request->file('avatar')->getRealPath())
                    ->cover(200, 200)->toPng();
            } catch (DecoderException $exception) {
                throw ValidationException::withMessages(['avatar' => 'The image could not be read. Please choose another image.']);
            }

            $newAvatar = 'avatars/'.Str::uuid().'.png';
            if (! Storage::disk('public')->put($newAvatar, (string) $thumbnail)) {
                throw ValidationException::withMessages(['avatar' => 'The image could not be saved. Please try again.']);
            }
            $data['avatar'] = $newAvatar;
        }

        try {
            $user->update($data);
        } catch (Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }
            throw $exception;
        }

        if ($newAvatar && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['delete' => 'You cannot delete your signed-in account.']);
        }
        $avatar = $user->avatar;
        $user->delete();
        if ($avatar) {
            Storage::disk('public')->delete($avatar);
        }
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $rules = [
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user)],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ];
        if ($user) {
            $rules['avatar'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'];
        }
        $data = $request->validate($rules);
        unset($data['avatar']);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        if ($user && $data['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        return $data;
    }
}
