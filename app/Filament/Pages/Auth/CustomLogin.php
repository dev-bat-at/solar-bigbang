<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class CustomLogin extends BaseLogin
{
    protected string $view = 'auth.custom-login';

    public function mount(): void
    {
        parent::mount();

        $email = request()->cookie('admin_remember_email');
        $password = request()->cookie('admin_remember_password');

        if ($email) {
            $this->form->fill([
                'email' => $email,
                'password' => $password,
                'remember' => true,
            ]);
        }
    }

    public function authenticate(): ?\Filament\Auth\Http\Responses\Contracts\LoginResponse
    {
        $response = parent::authenticate();

        $data = $this->form->getState();

        if ($data['remember'] ?? false) {
            \Illuminate\Support\Facades\Cookie::queue('admin_remember_email', $data['email'], 60 * 24 * 30);
            \Illuminate\Support\Facades\Cookie::queue('admin_remember_password', $data['password'], 60 * 24 * 30);
        } else {
            \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget('admin_remember_email'));
            \Illuminate\Support\Facades\Cookie::queue(\Illuminate\Support\Facades\Cookie::forget('admin_remember_password'));
        }

        return $response;
    }
}
