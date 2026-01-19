<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException; // Importar JWTException

class UsuarioController extends Controller
{
    /**
     * Lista todos os usuários (Admin/uso interno).
     */
    public function listar()
    {
        try {
            // Não deve retornar a senha (hashed) - O método makeHidden é melhor no modelo
            $usuarios = Usuario::all(['id', 'email', 'admin', 'status', 'created_at']);
            return response()->json($usuarios, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro interno ao listar usuários.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

/**
 * Lista todos os usuários com detalhes para o Painel Admin.
 */
public function listarGerenciamento()
{
    try {
        $adminLogado = auth()->user();
        
        // Reforço de segurança: verifica se quem pede é admin
        if (!$adminLogado || !$adminLogado->admin) {
            return response()->json(['message' => 'Acesso negado.'], Response::HTTP_FORBIDDEN);
        }

        // Busca todos os usuários ordenados por nome
        $usuarios = Usuario::orderBy('nome', 'asc')->get();
        
        return response()->json($usuarios->makeHidden(['senha']), Response::HTTP_OK);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Erro ao listar usuários para gerenciamento.',
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

/**
 * Remove um usuário permanentemente.
 */
public function excluir(string $id)
{
    try {
        $adminLogado = auth()->user();

        // Segurança: Apenas admin pode excluir
        if (!$adminLogado->admin) {
            return response()->json(['message' => 'Acesso negado.'], Response::HTTP_FORBIDDEN);
        }

        // Segurança: Impede que o admin exclua a si próprio
        if ($adminLogado->id == $id) {
            return response()->json(['message' => 'Você não pode excluir sua própria conta.'], Response::HTTP_BAD_REQUEST);
        }

        $usuario = Usuario::findOrFail($id);
        $usuario->delete();

        return response()->json(['message' => 'Usuário removido com sucesso!'], Response::HTTP_OK);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'Usuário não encontrado.'], Response::HTTP_NOT_FOUND);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Erro ao excluir usuário.'], 500);
    }
}
    
    /**
     * Busca um usuário pelo ID.
     */
    public function buscarPorId(string $id)
    {
        try {
            $usuario = Usuario::findOrFail($id);
            // Evita retornar o hash da senha
            return response()->json($usuario->makeHidden(['senha']), Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado.'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar usuário.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Busca um usuário pelo email (Query Parameter).
     */
    public function buscarPorEmail(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
            return response()->json([
                'message' => 'Parâmetro "email" é obrigatório na query string.'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $usuario = Usuario::where('email', $email)->firstOrFail();
            
            // Evita retornar o hash da senha
            return response()->json($usuario->makeHidden(['senha']), Response::HTTP_OK);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Cria um novo usuário (Cadastro).
     */
    public function criar(UsuarioRequest $request)
    {
        // Garante que a validação na UsuarioRequest foi executada e retorna os dados
        $dados = $request->validated(); 

        try {
            $usuario = new Usuario();
            
        
            $usuario->nome = $dados["nome"]; 
            
            $usuario->email = $dados["email"];
            
            $usuario->senha = Hash::make($dados["senha"]); 
            
            // Campos com valor padrão (Segurança):
            $usuario->admin = $dados["admin"] ?? false; 
            $usuario->status = $dados["status"] ?? "ativo";

            // Tenta salvar no DB
            $usuario->save(); 

            return response()->json([
                "message" => "Usuário criado com sucesso!",
                // Retorna o usuário sem a senha hasheada
                "usuario" => $usuario->makeHidden(['senha'])
            ], Response::HTTP_CREATED);
            
        } catch (\Exception $e) {
            // Este catch agora deve pegar erros de QueryException se houver algum outro campo NOT NULL faltando.
            return response()->json([
                "message" => "Erro ao criar usuário.",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Retorna os dados do usuário logado (GET /usuarios/me).
     * O token é validado pelo middleware 'jwt' na rota.
     */
    public function me()
    {
        try {
            // Pega o usuário do token, validado pelo middleware
            $usuarioLogado = auth()->user(); 

            if (!$usuarioLogado) {
                // Em caso de token inválido, o middleware já deveria ter retornado 401. 
                return response()->json(['message' => 'Usuário não autenticado.'], Response::HTTP_UNAUTHORIZED);
            }

            // Retorna o usuário sem a senha hasheada
            return response()->json($usuarioLogado->makeHidden(['senha']), Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro na autenticação do token.',
                'error' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
    
    /**
     * Atualiza os dados do usuário.
     */
    public function atualizar(string $id, UsuarioRequest $request)
    {
        try {
            // 1. Obter o usuário logado
            $usuarioLogado = auth()->user(); 

            // 2. Localizar o usuário a ser atualizado
            $usuario = Usuario::findOrFail($id);

            // 3. Verificação de Permissão: O usuário logado deve ser o mesmo OU um Admin.
            if ($usuarioLogado->id != $usuario->id && !$usuarioLogado->admin) {
                return response()->json([
                    "message" => "Você não tem permissão para atualizar este usuário."
                ], Response::HTTP_FORBIDDEN);
            }

            // Pega apenas os campos que podem ser atualizados
            $dados = $request->only(['nome', 'email', 'senha', 'admin', 'status']); 
            
            // SEGURANÇA: Previne que um usuário não-admin altere o campo 'admin'
            if (isset($dados['admin']) && !$usuarioLogado->admin) {
                 unset($dados['admin']);
            }
            
            // SEGURANÇA: Previne que um usuário não-admin altere o campo 'status' de outro usuário
            if (isset($dados['status']) && $usuarioLogado->id != $usuario->id && !$usuarioLogado->admin) {
                 unset($dados['status']);
            }

            // 4. Atualização dos Dados
            if (isset($dados["email"]) && $dados["email"] !== $usuario->email) {
                // Garante que o novo e-mail não está sendo usado por outro ID
                 $emailExistente = Usuario::where('email', $dados['email'])
                     ->where('id', '!=', $id)
                     ->exists();

                 if ($emailExistente) {
                     return response()->json([
                         "message" => "E-mail já está sendo usado por outro usuário!"
                     ], Response::HTTP_BAD_REQUEST);
                 }
                 $usuario->email = $dados["email"];
            }
            
            if (!empty($dados["senha"])) {
                $usuario->senha = Hash::make($dados["senha"]);
            }

            // Atualiza os outros campos (como nome, admin e status, se passados)
            $usuario->fill($dados);
            $usuario->save();

            return response()->json([
                "message" => "Usuário atualizado com sucesso!",
                "usuario" => $usuario->makeHidden(['senha'])
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuário não encontrado.'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erro ao atualizar usuário.",
                "error" => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Autentica o usuário e retorna o token JWT.
     */
    public function login(Request $request)
    {
        try {
            $dados = $request->validate([
                'email' => 'required|email',
                'senha' => 'required|string',
            ]);

            $usuario = Usuario::where('email', $dados['email'])->first();

            if (!$usuario) {
                return response()->json([
                    'message' => 'Credenciais inválidas.' // Mensagem mais genérica por segurança
                ], Response::HTTP_UNAUTHORIZED); 
            }

            if (!Hash::check($dados['senha'], $usuario->senha)) {
                return response()->json([
                    'message' => 'Credenciais inválidas.' // Mensagem mais genérica por segurança
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Gera o token JWT para o usuário autenticado
            $token = JWTAuth::fromUser($usuario);

            return response()->json([
                'message' => 'Login realizado com sucesso!',
                'usuario' => $usuario->makeHidden(['senha']),
                'token' => $token
            ], Response::HTTP_OK);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados de entrada inválidos.',
                'errors' => $e->errors()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao tentar fazer login.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}