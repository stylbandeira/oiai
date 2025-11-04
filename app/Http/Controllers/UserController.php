<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = User::query();
        $user = Auth::user();

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('cpf', 'like', $searchTerm);
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $validSortFields = ['name', 'points', 'reputation', 'created_at'];

        if (in_array($sortField, $validSortFields)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($user->type === 'admin') {
            $query->withTrashed();
        }

        $perPage = $request->per_page ?? 10;

        $users = $query->paginate($perPage);

        if ($request->user()->type === 'admin') {
            return AdminUserResource::collection($users);
        }

        return response([
            'message' => 'Erro de autorização'
        ], 403);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|required',
            'type' => 'string|required',
            'email' => 'email|required',
            'cpf' => 'string|required',
            'status' => 'string',
            'companies' => 'array',
            'companies.*' => 'exists:company,id'
        ]);

        Log::alert($request->all());

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        // $temporaryPassword = Str::uuid();

        // $user = User::create([
        //     'name' => $request->name,
        //     'type' => $request->type,
        //     'email' => $request->email,
        //     'cpf' => $request->cpf,
        //     'status' => $request->status,
        //     'password' => bcrypt($temporaryPassword),
        //     'email_verified_at' => null,
        //     'must_change_password' => true,
        // ]);

        // if ($request->type === 'company' && $request->has('companies')) {
        //     $user->companies()->attach($request->companies);
        // }

        // return response([
        //     'message' => 'uau'
        // ]);

        DB::beginTransaction();

        try {
            $temporaryPassword = Str::uuid();

            $user = User::create([
                'name' => $request->name,
                'type' => $request->type,
                'email' => $request->email,
                'cpf' => $request->cpf,
                'status' => $request->status,
                'password' => bcrypt($temporaryPassword),
                'email_verified_at' => null,
                'must_change_password' => true,
            ]);

            if ($request->type === 'company' && $request->has('companies')) {
                $user->companies()->attach($request->companies);
            }

            DB::commit();

            $this->sendWelcomeEmail($user, $temporaryPassword);

            return response([
                'message' => 'Usuário criado com sucesso!',
                'user' => $user
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::alert("Erro");

            return response()->json([
                'message' => 'Erro ao criar usuário',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return void
     */
    public function destroy(User $user)
    {
        Log::alert('Error');
        if (!$user->companies) {
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
        } else {
            $user->delete();
        }

        return response([
            'message' => 'Usuário excluído com sucesso!'
        ]);
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
    public function export(Request $request, ExportService $exportService)
    {
        $query = User::query();
        $user = Auth::user();

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('cpf', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('sort_by') && $request->has('sort_order')) {
            $validSortFields = ['name', 'points', 'reputation', 'created_at'];
            if (in_array($request->sort_by, $validSortFields)) {
                $query->orderBy($request->sort_by, $request->sort_order);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($user->type === 'admin') {
            $query->withTrashed();
        }

        $users = $query->get();

        $columns = [
            'ID' => 'id',
            'Nome' => 'name',
            'Email' => 'email',
            'CPF' => 'cpf',
            'Tipo' => function ($user) {
                $types = [
                    'client' => 'Cliente',
                    'company' => 'Empresa',
                    'admin' => 'Administrador'
                ];
                return $types[$user->type] ?? $user->type;
            },
            'Pontos' => 'points',
            'Reputação' => 'reputation',
            'Status' => function ($user) {
                $statuses = [
                    'active' => 'Ativo',
                    'inactive' => 'Inativo',
                    'suspended' => 'Suspenso'
                ];
                return $statuses[$user->status] ?? $user->status;
            },
            'Data de Criação' => function ($user) {
                return $user->created_at->format('d/m/Y H:i:s');
            },
            'Data de Exclusão' => function ($user) {
                return $user->deleted_at ? $user->deleted_at->format('d/m/Y H:i:s') : '-';
            },
        ];

        return $exportService->exportToCSV($users, $columns, 'usuarios');
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
