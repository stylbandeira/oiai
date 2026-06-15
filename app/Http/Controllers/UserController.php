<?php

namespace App\Http\Controllers;

use App\Actions\User\DashboardDataUserAction;
use App\Actions\User\DestroyUserAction;
use App\Actions\User\ExportUserAction;
use App\Actions\User\IndexUserAction;
use App\Actions\User\RevertDestroyUserAction;
use App\Actions\User\ShowUserAction;
use App\Actions\User\StoreUserAction;
use App\Actions\User\UpdateUserAction;
use App\Exports\Mappers\UserExportMapper;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class);
    }

    public function dashboardData(Request $request, DashboardDataUserAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Display a listing of the resource.
     *
     * @param IndexUserRequest $request
     * @return void
     */
    public function index(IndexUserRequest $request, IndexUserAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Store a new user.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(UserStoreRequest $request, StoreUserAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user, ShowUserAction $action)
    {
        return $action->execute($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UserUpdateRequest $request
     * @param User $user
     * @return void
     */
    public function update(UserUpdateRequest $request, User $user, UpdateUserAction $action)
    {
        return $action->execute($request, $user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return void
     */
    public function destroy(User $user, DestroyUserAction $action)
    {
        return $action->execute($user);
    }

    /**
     * Função para reverter deleção de usuário
     *
     * @param User $user
     * @return void
     */
    public function revertDestroy(User $user, RevertDestroyUserAction $action)
    {
        return $action->execute($user);
    }

    /**
     * Export users to CSV
     */
    public function export(
        IndexUserRequest $request,
        ExportService $exportService,
        UserExportMapper $mapper,
        ExportUserAction $action

    ) {
        return $action->execute($request, $exportService, $mapper);
    }
}
