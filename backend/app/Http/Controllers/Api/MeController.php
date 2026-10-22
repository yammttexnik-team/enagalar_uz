<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load(['roles', 'activeBadges']));
    }

    /** Ro'yxatdan o'tgandan keyin rol tanlash: ota-ona yoki enaga (ikkalasi ham bo'lishi mumkin). */
    public function addRole(Request $request): UserResource
    {
        $data = $request->validate([
            'role' => ['required', Rule::in([Role::Parent->value, Role::Nanny->value])],
        ]);

        $user = $request->user();
        $user->assignRole($data['role']);

        match (Role::from($data['role'])) {
            Role::Parent => $user->parentProfile()->firstOrCreate(),
            Role::Nanny => $user->nannyProfile()->firstOrCreate(),
        };

        return new UserResource($user->load(['roles', 'activeBadges']));
    }
}
