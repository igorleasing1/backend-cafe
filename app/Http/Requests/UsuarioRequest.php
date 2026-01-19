<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsuarioRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
   public function rules(): array
{
    // Captura o ID do usuário da rota para ignorar na validação de e-mail único
    $usuarioId = $this->route('id');

    // Regras básicas para criação (POST)
    $rules = [
        'nome' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:usuarios,email',
        'senha' => [
            'required',
            'string',
            'min:8',
            'regex:/[A-Z]/', 
            'regex:/[a-z]/',      
            'regex:/[0-9]/',   
            'regex:/[@$!%*?&#^()_+\-=\[\]{};:"\\|,.<>\/?]/'
        ],
        'admin' => 'sometimes|boolean',
        'status' => 'sometimes|string'
    ];

    // Se for uma atualização (PATCH ou PUT)
    if ($this->isMethod('patch') || $this->isMethod('put')) {
        // Torna os campos opcionais usando 'sometimes'
        $rules['nome'] = 'sometimes|string|max:255';
        $rules['senha'] = [
            'sometimes', // Valida apenas se o campo for enviado
            'nullable',  // Permite que seja nulo (vazio)
            'string',
            'min:8',
            'regex:/[A-Z]/', 
            'regex:/[a-z]/',      
            'regex:/[0-9]/',   
            'regex:/[@$!%*?&#^()_+\-=\[\]{};:"\\|,.<>\/?]/'
        ];
        // Permite o e-mail atual do próprio usuário ao verificar 'unique'
        $rules['email'] = 'sometimes|string|email|max:255|unique:usuarios,email,' . $usuarioId;
    }

    return $rules;
}
}