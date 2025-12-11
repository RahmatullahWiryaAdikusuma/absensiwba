<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Components\Component;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction; // <--- Alias untuk Notifikasi
use Filament\Actions\Action; // <--- Import untuk Action Halaman
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use App\Models\User;
use App\Filament\Resources\UserResource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Placeholder;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),

                        // Link Custom di Bawah
                        Placeholder::make('login_link')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <div class="text-center mt-4 text-sm text-gray-600">
                                    Sudah punya akun? 
                                    <a href="'.filament()->getLoginUrl().'" class="font-bold text-primary-600 hover:text-primary-500 transition">
                                        Login di sini
                                    </a>
                                </div>
                            ')),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    // TIMPA FUNGSI INI UNTUK MENGHILANGKAN TOMBOL LOGIN BAWAAN DI ATAS
    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label(__('filament-panels::pages/auth/register.actions.login.label'))
            ->url(filament()->getLoginUrl())
            ->hidden(true); // <--- Sembunyikan
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        $data = $this->form->getState();

        // 1. Buat User (Status Non-Aktif)
        $user = $this->getUserModel()::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => false, 
        ]);

        // 2. Assign Role Karyawan
        $user->assignRole('karyawan'); 

        event(new Registered($user));

        // 3. Kirim Notifikasi ke Admin
        $admins = User::role('super_admin')->get();
        
        foreach ($admins as $admin) {
            Notification::make()
                ->title('Pendaftaran User Baru')
                ->body("User **{$user->name}** mendaftar dan menunggu persetujuan.")
                ->actions([
                    NotificationAction::make('review') // Gunakan alias NotificationAction
                        ->button()
                        ->label('Cek User')
                        ->markAsRead()
                        ->url(UserResource::getUrl('edit', ['record' => $user])),
                ])
                ->warning()
                ->sendToDatabase($admin);
        }

        Notification::make()
            ->title('Registrasi Berhasil')
            ->body('Akun telah dibuat. Mohon tunggu persetujuan Admin untuk login.')
            ->success()
            ->send(); 

        return app(RegistrationResponse::class);
    }
}