<?php

namespace App\Http\Controllers;

use App\Exports\Mappers\UserExportMapper;
use App\Http\Requests\User\IndexUserRequest;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\ClientDashboardResource;
use App\Http\Resources\ClientUserResource;
use App\Http\Resources\CompanyUserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\ExportService;
use App\Services\User\StoreUserService;
use App\Services\User\UpdateUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(
        private UpdateUserService $updateUserService,
        private StoreUserService $storeUserService,
        private UserRepository $userRepository,
    ) {}

    public function dashboardData(Request $request)
    {
        $user = User::with('recentActivity')->findOrFail(Auth::id());

        return response([
            'dashboardData' => new ClientDashboardResource($user)
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param IndexUserRequest $request
     * @return void
     */
    public function index(IndexUserRequest $request)
    {
        if ($request->user()->type !== 'admin') {
            return response([
                'message' => 'Não autorizado'
            ], 403);
        }

        $users = $this->userRepository->paginate(
            $request->validated(),
            [
                'with_trashed' => $request->user()->type === 'admin'
            ]
        );

        if ($request->user()->type === 'admin') {
            return AdminUserResource::collection($users);
        }
    }

    /**
     * Store a new user.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(UserStoreRequest $request)
    {
        try {
            $user = $this->storeUserService->execute(
                $request->validated(),
                Auth::id()
            );

            return response([
                'message' => 'Usuário criado com sucesso!',
                'user' => $user
            ]);
        } catch (\Throwable $th) {
            Log::error('Erro ao criar usuário', [
                'error' => $th->getMessage()
            ]);

            return response([
                'error' => $th->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $current_user = Auth::user();
        $user->load(['companies', 'pendingCompanies', 'activeCompanies', 'events']);

        if ($current_user->type === 'admin') {
            return new AdminUserResource($user);
        } else if ($current_user->type === 'client' && $user->id === $current_user->id) {
            return new ClientUserResource($user);
        } else if ($current_user->type === 'company') {
            return new CompanyUserResource($user);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UserUpdateRequest $request
     * @param User $user
     * @return void
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $user = $this->updateUserService->execute(
            $user,
            $request->validated(),
            Auth::id()
        );

        return response([
            'message' => 'Usuário editado com sucesso!',
            'user' => new AdminUserResource($user),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return void
     */
    public function destroy(User $user)
    {
        $user->load('companies');

        if (count($user->companies)) {
            return response([
                'message' => 'Apague a relação entre usuário e empresa primeiro.'
            ], 400);
        }

        if ($user->type === 'admin') {
            return response([
                'message' => 'Infelizmente não é possível deletar usuários do tipo admin.'
            ], 400);
        }

        if ($user->deleted_at) {
            $user->deleted_at == null;

            return response([
                'message' => 'Usuário reativado com sucesso!'
            ]);
        } else {
            $user->delete();

            return response([
                'message' => 'Usuário excluído com sucesso!'
            ]);
        }
    }

    /**
     * Função para reverter deleção de usuário
     *
     * @param User $user
     * @return void
     */
    public function revertDestroy(Int $id)
    {
        $user = User::withTrashed()->find($id);

        if (!$user->deleted_at) {
            return response([
                'message' => 'Usuário não precisa ser reativado.'
            ], 400);
        }

        $user->restore();

        return response([
            'message' => 'Usuário revertido',
            'user' => new AdminUserResource($user)
        ]);
    }

    /**
     * Export users to CSV
     */
    public function export(
        IndexUserRequest $request,
        ExportService $exportService,
        UserExportMapper $mapper

    ) {
        $users = $this->userRepository->list($request->validated());

        return $exportService->exportToCSV(
            $users,
            $mapper->columns(),
            'usuarios'
        );
    }

    /**
     * TODO - Criar sendWelcomeEmail para enviar email de boas vindas para usuários criados
     * usando um usuário do tipo admin.
     *
     * @param User $user
     * @param String $password
     * @return void
     */
    private function sendWelcomeEmail(User $user, String $password)
    {
        return true;
    }
}
