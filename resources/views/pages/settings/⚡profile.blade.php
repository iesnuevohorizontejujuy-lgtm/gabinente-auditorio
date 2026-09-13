<?php

use App\Concerns\ProfileValidationRules;
use App\Models\Carrera;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public string $carreras = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = $this->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->carreras = $user->carreras()->orderBy('nombre')->pluck('nombre')->implode(', ');
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = $this->user();

        $validated = $this->validate([
            ...$this->profileRules($user->id),
            'carreras' => $user->isProfessor()
                ? ['required', 'string', 'max:1250']
                : ['nullable', 'string', 'max:1250'],
        ]);
        $careerNames = $user->isProfessor() ? $this->careerNames() : [];

        DB::transaction(function () use ($user, $validated, $careerNames): void {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            $careerIds = collect($careerNames)
                ->map(fn (string $name): int => Carrera::query()->firstOrCreate(['nombre' => $name])->id)
                ->all();

            $user->carreras()->sync($careerIds);
        });

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    /** @return list<string> */
    private function careerNames(): array
    {
        $names = collect(explode(',', $this->carreras))
            ->map(fn (string $name): string => Str::squish($name))
            ->filter()
            ->unique(fn (string $name): string => Str::lower($name))
            ->values();

        if ($names->isEmpty()) {
            throw ValidationException::withMessages([
                'carreras' => 'Ingresá al menos una carrera.',
            ]);
        }

        if ($names->count() > 10) {
            throw ValidationException::withMessages([
                'carreras' => 'Podés asociar hasta 10 carreras.',
            ]);
        }

        if ($names->contains(fn (string $name): bool => Str::length($name) > 120)) {
            throw ValidationException::withMessages([
                'carreras' => 'Cada carrera puede tener hasta 120 caracteres.',
            ]);
        }

        return $names->all();
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your full name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Full name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            @if (auth()->user()->isProfessor())
                <div>
                    <flux:input
                        wire:model="carreras"
                        label="Carreras en las que dictás clases"
                        placeholder="Ej.: Enfermería, Desarrollo de Software"
                        required
                    />
                    <flux:text class="mt-2 text-sm">
                        Separá varias carreras con comas. Esta información se mostrará en tus reservas.
                    </flux:text>
                </div>
            @endif

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
