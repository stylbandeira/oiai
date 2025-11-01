<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\Request;

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
    public function create()
    {
        //
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

        $user->delete();

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
    public function revertDestroy(User $user)
    {
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

    public function
}
