<x-layouts.app :title="'Личный кабинет — ' . config('app.name')">
    <h1 class="mb-4 text-xl font-semibold">Добро пожаловать, {{ auth()->user()->name }}!</h1>
    <p class="text-slate-600">Вы вошли как {{ auth()->user()->email }}.</p>
</x-layouts.app>
