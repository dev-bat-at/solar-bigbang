<x-filament-panels::page.simple>
<div class="login-shell">
    <style>
        .login-shell {
            position: fixed;
            inset: 0;
            z-index: 9999;
            min-height: 100vh;
            width: 100%;
            overflow: hidden;
            font-family: sans-serif;
            background: #0f172a;
        }

        .login-background,
        .login-background-image,
        .login-background-overlay,
        .login-background-vignette {
            position: absolute;
            inset: 0;
        }

        .login-background {
            overflow: hidden;
        }

        .login-background-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }

        .login-background-overlay {
            background:
                linear-gradient(90deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0) 22%),
                linear-gradient(115deg, rgba(15, 23, 42, 0.28) 0%, rgba(15, 23, 42, 0.1) 40%, rgba(15, 23, 42, 0.32) 100%);
        }

        .login-background-vignette {
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.18), transparent 24%),
                radial-gradient(circle at bottom right, rgba(15, 23, 42, 0.32), transparent 34%);
        }

        .login-content {
            position: relative;
            z-index: 2;
            display: flex;
            min-height: 100vh;
            width: 100%;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }

        .login-floating-panel {
            width: 100%;
            max-width: 28rem;
        }

        .login-card {
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.78);
            border-radius: 2rem;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.22);
            backdrop-filter: blur(18px);
        }

        .login-card-inner {
            padding: 2rem 1.5rem 1.6rem;
        }

        .login-branding {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.75rem;
            text-align: center;
        }

        .login-brand-logo {
            max-width: 150px;
            max-height: 110px;
            width: auto;
            object-fit: contain;
        }

        .login-site-name {
            margin: 0;
            color: #0f172a;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .login-title {
            margin: 0;
            color: #0f172a;
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .login-subtitle {
            margin: 0.45rem 0 0;
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .login-form {
            display: flex;
            width: 100%;
            flex-direction: column;
            gap: 1.25rem;
        }

        .login-form .fi-ac {
            width: 100%;
        }

        .login-form .fi-btn {
            width: 100%;
            border: none !important;
            background: linear-gradient(90deg, #f97316 0%, #fb7185 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 14px 30px rgba(249, 115, 22, 0.28);
        }

        .login-register-text {
            margin-top: 1.25rem;
            text-align: center;
            color: #64748b;
            font-size: 0.875rem;
        }

        .login-register-link {
            color: #ea580c;
            font-weight: 600;
            text-decoration: none;
        }

        .login-register-link:hover {
            color: #c2410c;
        }

        .login-side-copy {
            display: none;
        }

        @media (min-width: 1024px) {
            .login-content {
                justify-content: flex-start;
                padding: 2.5rem 3rem;
            }

            .login-floating-panel {
                margin-left: clamp(2rem, 7vw, 7rem);
            }

            .login-side-copy {
                position: absolute;
                right: clamp(2rem, 5vw, 5rem);
                bottom: clamp(2rem, 5vw, 4rem);
                z-index: 2;
                display: block;
                max-width: 34rem;
                color: #ffffff;
                text-align: left;
            }
        }

        .login-copy-kicker {
            margin: 0 0 1rem;
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.78);
        }

        .login-copy-title {
            margin: 0;
            font-size: clamp(2.2rem, 4.2vw, 4.4rem);
            font-weight: 900;
            line-height: 0.96;
            text-transform: uppercase;
            text-shadow: 0 12px 30px rgba(15, 23, 42, 0.2);
        }

        .login-copy-description {
            margin: 1.2rem 0 0;
            max-width: 30rem;
            color: rgba(255, 255, 255, 0.92);
            font-size: 1rem;
            line-height: 1.8;
        }

        .login-copy-outline {
            position: absolute;
            top: clamp(2rem, 9vw, 6rem);
            right: clamp(2rem, 5vw, 5rem);
            z-index: 1;
            font-size: clamp(2.8rem, 7vw, 6.5rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
            text-transform: uppercase;
            color: transparent;
            -webkit-text-stroke: 1px rgba(255, 255, 255, 0.28);
            pointer-events: none;
        }

        .fi-simple-page-form {
            all: unset;
        }

        .fi-simple-page-form label,
        .fi-simple-page-form span,
        .fi-fo-field-wrp-label,
        .fi-fo-field-wrp-label label,
        .fi-fo-field-wrp-label span {
            color: #334155 !important;
            font-weight: 600 !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .fi-input,
        .fi-input input {
            color: #0f172a !important;
            background-color: rgba(255, 255, 255, 0.92) !important;
        }

        .fi-input::placeholder,
        .fi-input input::placeholder {
            color: #94a3b8 !important;
        }

        .fi-input-wrp {
            border: 1px solid #dbe2ea !important;
            background-color: rgba(255, 255, 255, 0.92) !important;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
        }

        .dark .login-card {
            border-color: rgba(148, 163, 184, 0.16);
            background: rgba(15, 23, 42, 0.78);
            box-shadow: 0 30px 80px rgba(2, 6, 23, 0.48);
        }

        .dark .login-site-name,
        .dark .login-title {
            color: #f8fafc;
        }

        .dark .login-subtitle,
        .dark .login-register-text {
            color: #94a3b8;
        }

        .dark .login-register-link {
            color: #fb923c;
        }

        .dark .fi-simple-page-form label,
        .dark .fi-simple-page-form span,
        .dark .fi-fo-field-wrp-label,
        .dark .fi-fo-field-wrp-label label,
        .dark .fi-fo-field-wrp-label span {
            color: #e2e8f0 !important;
        }

        .dark .fi-input-wrp {
            border: 1px solid #334155 !important;
            background-color: rgba(15, 23, 42, 0.78) !important;
            box-shadow: none;
        }

        .dark .fi-input,
        .dark .fi-input input {
            color: #f8fafc !important;
            background-color: rgba(15, 23, 42, 0.78) !important;
        }

        .dark .fi-input::placeholder,
        .dark .fi-input input::placeholder {
            color: #64748b !important;
        }
    </style>

    @php
        $siteName = \App\Models\SystemSetting::get('app_name', 'Solar BigBang');
        $loginSubtitle = \App\Models\SystemSetting::get('login_subtitle', 'Nền tảng quản lý năng lượng số');
        $banner = \App\Models\SystemSetting::getUrl('login_background_image', 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&q=80&w=2075&ixlib=rb-4.0.3');
        $logo = \App\Models\SystemSetting::getUrl('app_logo', asset('vendor/filament/images/logo.svg'));
        $logoUrl = \App\Models\SystemSetting::get('app_logo');
    @endphp

    <div class="login-background">
        <img src="{{ $banner }}" alt="Banner" class="login-background-image" loading="eager" decoding="async" fetchpriority="high" />
        <div class="login-background-overlay"></div>
        <div class="login-background-vignette"></div>
    </div>

    <div class="login-copy-outline">Welcome</div>

    <div class="login-content">
        <div class="login-floating-panel">
            <div class="login-card">
                <div class="login-card-inner">
                    <div class="login-branding">
                        @if($logoUrl)
                            <img src="{{ $logo }}" alt="{{ $siteName }}" class="login-brand-logo" />
                        @else
                            <h2 class="login-site-name">{{ $siteName }}</h2>
                        @endif

                        <div>
                            <h2 class="login-title">Đăng nhập</h2>
                        </div>
                    </div>

                    <div class="login-form">
                        {{ $this->content }}
                    </div>

                    @if (filament()->hasRegistration())
                        <p class="login-register-text">
                            {{ __('filament-panels::pages/auth/login.actions.register.before') }}
                            <a href="{{ filament()->getRegistrationUrl() }}" class="login-register-link">
                                {{ __('filament-panels::pages/auth/login.actions.register.label') }}
                            </a>
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="login-side-copy">
            <p class="login-copy-kicker">{{ strtoupper($siteName) }}</p>
            <h1 class="login-copy-title">{{ $loginSubtitle }}</h1>
            <p class="login-copy-description">
                Đăng nhập để theo dõi dự án, vận hành hệ thống và quản trị dữ liệu trên một không gian làm việc trực quan, hiện đại.
            </p>
        </div>

        @livewire(\Filament\Livewire\Notifications::class)
    </div>
</div>
</x-filament-panels::page.simple>
