<x-layouts.guest :title="'Вход — ' . config('app.name')">
    <h1 class="mb-6 text-xl font-semibold">Вход</h1>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-form-input label="Email" name="email" type="email" autofocus />

        <x-form-input label="Пароль" name="password" type="password" />

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                Запомнить меня
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm text-emerald-700 hover:underline">
                    Забыли пароль?
                </a>
            @endif
        </div>

        <button
            type="submit"
            class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        >
            Войти
        </button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-slate-600">
            Ещё нет аккаунта?
            <a href="{{ route('register') }}" class="font-medium text-emerald-700 hover:underline">
                Зарегистрироваться
            </a>
        </p>
    @endif
</x-layouts.guest>
