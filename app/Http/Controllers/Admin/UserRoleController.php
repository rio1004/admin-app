<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageUserAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRolesRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserRoleController extends Controller
{
    public function __invoke(UserRolesRequest $request, User $user, ManageUserAccess $access): RedirectResponse
    {
        $access->assignRoles($request->user(), $user, array_values(array_map(intval(...), $request->validated('roles'))));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Roles updated.']);

        return back();
    }
}
