<x-layouts.guest :title="'Новый пароль — ' . config('app.name')">
    <h1 class="mb-6 text-xl font-semibold">Новый пароль</h1>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form-input label="Email" name="email" type="email" :value="$request->email" autofocus />

        <x-form-input label="Новый пароль" name="password" type="password" />

        <x-form-input label="Повторите пароль" name="password_confirmation" type="password" />

        <button
            type="submit"
            class="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
        >
            Сохранить пароль
        </button>
    </form>
</x-layouts.guest>
