<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход · SafeDeal Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700|sora:500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center px-4">
    <div class="fade-up w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7">
                    <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3A5.25 5.25 0 0 0 12 1.5Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" />
                </svg>
            </div>
            <h1 class="font-display text-3xl font-semibold text-ink">SafeDeal</h1>
            <p class="mt-2 text-sm text-muted">Вход в административную панель</p>
        </div>

        <form method="POST" action="{{ route('admin.login.submit') }}" class="panel p-6 shadow-sm">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-ink">Email</label>
                    <input type="email" name="email" value="{{ old('email', 'admin@safedeal.test') }}" class="input" required autofocus>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-ink">Пароль</label>
                    <input type="password" name="password" value="Password123" class="input" required>
                </div>
                @error('email')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
                <label class="flex items-center gap-2 text-sm text-muted">
                    <input type="checkbox" name="remember" class="rounded border-line text-brand-500 focus:ring-brand-500">
                    Запомнить меня
                </label>
                <button class="btn-primary w-full">Войти</button>
            </div>
        </form>
    </div>
</body>
</html>
