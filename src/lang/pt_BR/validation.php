<?php

return [

    'accepted' => 'O campo :attribute deve ser aceito.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'current_password' => 'A senha atual está incorreta.',
    'email' => 'O campo :attribute deve ser um endereço de e-mail válido.',
    'max' => [
        'array' => 'O campo :attribute não pode ter mais do que :max itens.',
        'file' => 'O campo :attribute não pode ser maior que :max kilobytes.',
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'array' => 'O campo :attribute deve ter pelo menos :min itens.',
        'file' => 'O campo :attribute deve ter pelo menos :min kilobytes.',
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'password' => [
        'letters' => 'O campo :attribute deve conter pelo menos uma letra.',
        'mixed' => 'O campo :attribute deve conter pelo menos uma letra maiúscula e uma minúscula.',
        'numbers' => 'O campo :attribute deve conter pelo menos um número.',
        'symbols' => 'O campo :attribute deve conter pelo menos um símbolo.',
        'uncompromised' => 'O :attribute informado apareceu em um vazamento de dados. Escolha um :attribute diferente.',
    ],
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'O :attribute informado já está em uso.',
    'url' => 'O campo :attribute deve ser uma URL válida.',

    'custom' => [
        'email' => [
            'unique' => 'Este e-mail já está em uso.',
        ],
    ],

    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'password_confirmation' => 'confirmação de senha',
        'current_password' => 'senha atual',
    ],

];
