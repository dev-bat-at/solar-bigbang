<div
    style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; background: white; display: flex; flex-direction: row; font-family: sans-serif; overflow: hidden;">
    <style>
        .split-left {
            display: none;
        }

        .split-right {
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            overflow-y: auto;
        }

        .split-form-container {
            width: 100%;
            max-w-sm;
            max-width: 400px;
        }

        @media (min-width: 1024px) {
            .split-left {
                display: block;
                width: 70%;
                position: relative;
                overflow: hidden;
                background-color: #000;
            }

            .split-right {
                width: 30%;
            }
        }

        /* Overriding filament internal card styles so it doesn't look messy */
        .fi-simple-page-form {
            all: unset;
        }

        /* Force label color to be visible on white background */
        .fi-simple-page-form label,
        .fi-simple-page-form span,
        .fi-fo-field-wrp-label,
        .fi-fo-field-wrp-label label,
        .fi-fo-field-wrp-label span {
            color: #374151 !important;
            font-weight: 600 !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        /* Ensure input text and placeholder are visible */
        .fi-input,
        .fi-input input {
            color: #111827 !important;
            background-color: white !important;
        }

        .fi-input::placeholder {
            color: #9ca3af !important;
        }

        /* Border for inputs to be clear */
        .fi-input-wrp {
            border: 1px solid #d1d5db !important;
            background-color: white !important;
        }
    </style>

    @php
        $siteName = \App\Models\SystemSetting::get('app_name', 'Solar BigBang');
        $banner = \App\Models\SystemSetting::getUrl('login_background_image', 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&q=80&w=2075&ixlib=rb-4.0.3');
        $logo = \App\Models\SystemSetting::getUrl('app_logo', asset('vendor/filament/images/logo.svg'));
        $logoUrl = \App\Models\SystemSetting::get('app_logo');
    @endphp

    {{-- Left Side: Image (Full nét) --}}
    <div class="split-left">
        <img src="{{ $banner }}" alt="Banner"
            style="width: 100%; height: 100%; object-fit: cover; object-position: center;" />
    </div>

    {{-- Right Side: Login Form --}}
    <div class="split-right light">
        <div class="split-form-container">
            {{-- Logo & Site Name --}}
            <div
                style="display: flex; flex-direction: column; align-items: center; gap: 1rem; margin-bottom: 2rem; text-align: center;">
                @if($logoUrl)
                    <img src="{{ $logo }}" alt="{{ $siteName }}" style="max-height: 110px; object-fit: contain;" />
                @else
                    <h2 style="font-size: 1.875rem; font-weight: bold; color: #111827;">{{ $siteName }}</h2>
                @endif
                <h2 style="font-size: 1.5rem; font-weight: 700; color: #111827;">
                    Đăng nhập
                </h2>
            </div>

            {{-- Form Component (Fixed cho Filament v5) --}}
            <form wire:submit="authenticate" style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">
                {{ $this->form }}

                <!-- Nút Submit Đăng nhập -->
                <x-filament::button type="submit" size="lg"
                    style="width: 100%; margin-top: 1.5rem; background-color: #5145E4; color: white;">
                    Đăng nhập
                </x-filament::button>
            </form>

            {{-- Registration Link (Nếu có) --}}
            @if (filament()->hasRegistration())
                <p style="text-align: center; font-size: 0.875rem; color: #4B5563; margin-top: 1.5rem;">
                    {{ __('filament-panels::pages/auth/login.actions.register.before') }}
                    <a href="{{ filament()->getRegistrationUrl() }}" style="font-weight: 500; color: #5145E4;">
                        {{ __('filament-panels::pages/auth/login.actions.register.label') }}
                    </a>
                </p>
            @endif
        </div>

        {{-- Notifications --}}
        @livewire(\Filament\Livewire\Notifications::class)
    </div>
</div>