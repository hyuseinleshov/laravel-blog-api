<?php

namespace App\Actions\Auth;

use App\Enums\AuthorStatus;
use App\Models\Author;
use Illuminate\Support\Facades\Hash;

class RegisterAuthorAction
{
    public function execute(array $data): Author
    {
        return Author::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => AuthorStatus::ACTIVE,
        ]);
    }
}
