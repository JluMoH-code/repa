<x-layouts.guest :title="'Восстановление пароля — ' . config('app.name')">
    <h1 class="mb-2 text-xl font-semibold">Восстановление пароля</h1>
    <p class="mb-6 text-sm text-slate-600">
        Укажите email, и мы вышлем ссылку для сброса пароля.
    </p>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <x-form-input label="Email" name="email" type="email" autofocus />

        <button
            type="submit"
            class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        >
            Отправить ссылку
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:underline">
            Вернуться ко входу
        </a>
    </p>
</x-layouts.guest>
