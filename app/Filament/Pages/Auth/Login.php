<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\Component;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\Action; // <--- PENTING: Import ini

class Login extends BaseLogin
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                        
                        // Link Custom di Bawah Form
                        Placeholder::make('register_link')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <div class="text-center mt-4 text-sm text-gray-600">
                                    Belum punya akun? 
                                    <a href="'.filament()->getRegistrationUrl().'" class="font-bold text-primary-600 hover:text-primary-500 transition">
                                        Daftar Sekarang
                                    </a>
                                </div>
                            ')),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    // --- FUNGSI PENGHILANG TOMBOL ATAS ---
    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('filament-panels::pages/auth/login.actions.register.label'))
            ->url(filament()->getRegistrationUrl())
            ->hidden(true); // <--- INI KUNCINYA (Sembunyikan tombol)
    }
}