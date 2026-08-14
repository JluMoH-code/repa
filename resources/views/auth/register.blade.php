<x-layouts.guest :title="'Регистрация — ' . config('app.name')">
    <h1 class="mb-6 text-xl font-semibold">Регистрация</h1>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-form-input label="Имя" name="name" autofocus />

        <x-form-input label="Email" name="email" type="email" />

        <x-form-input label="Пароль" name="password" type="password" />

        <x-form-input label="Повторите пароль" name="password_confirmation" type="password" />

        <button
            type="submit"
            class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        >
            Создать аккаунт
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Уже есть аккаунт?
        <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:underline">
            Войти
        </a>
    </p>
</x-layouts.guest>
