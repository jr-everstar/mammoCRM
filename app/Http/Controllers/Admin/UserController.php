<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:admin,staff,sales'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $users = User::query()
            ->with('roles')
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->role($role))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'filters' => $filters,
            'counts' => [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'inactive' => User::where('status', 'inactive')->count(),
                'admins' => User::role('admin')->count(),
                'staff' => User::role('staff')->count(),
                'sales' => User::role('sales')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $microsoftOnly = $request->boolean('must_use_microsoft_login');

        $data = $request->validate($this->rules($microsoftOnly));

        $data['password'] = Hash::make(Str::random(64));
        $data['must_use_microsoft_login'] = $microsoftOnly;
        $data['status'] = 'active';
        $role = $data['role'];
        unset($data['role']);

        $user = User::create($data);
        $user->syncRoles([$role]);

        $invitationLink = $this->sendInvitationFor($user);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', '用戶已建立，邀請電郵已發送。')
            ->with('invitation_link', $invitationLink);
    }

    public function show(User $user): View
    {
        $user->load('roles');

        return view('admin.users.show', ['user' => $user]);
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $microsoftOnly = $request->boolean('must_use_microsoft_login');

        $data = $request->validate($this->rules($microsoftOnly, $user));

        $role = $data['role'];
        unset($data['role']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['must_use_microsoft_login'] = $microsoftOnly;

        $user->update($data);

        if ($user->mustUseMicrosoftLogin()) {
            $user->passkeys()->delete();
        }

        $user->syncRoles([$role]);

        return redirect()->route('admin.users.show', $user)->with('status', '用戶已更新。');
    }

    public function sendInvitation(User $user): RedirectResponse
    {
        $invitationLink = $this->sendInvitationFor($user);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', '邀請電郵已重新發送。')
            ->with('invitation_link', $invitationLink);
    }

    private function rules(bool $microsoftOnly, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'password' => ['nullable', 'prohibited'],
            'role' => ['required', 'in:admin,staff,sales'],
        ];

        if ($user) {
            $rules['status'] = ['required', 'in:active,inactive'];
        }

        return $rules;
    }

    private function sendInvitationFor(User $user): string
    {
        $invitationLink = $this->invitationLink($user);

        $user->notify(new UserInvitation($invitationLink, $user->mustUseMicrosoftLogin()));

        return $invitationLink;
    }

    private function invitationLink(User $user): string
    {
        if ($user->mustUseMicrosoftLogin()) {
            return route('login');
        }

        $token = Password::broker(config('fortify.passwords'))->createToken($user);

        return route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }
}
