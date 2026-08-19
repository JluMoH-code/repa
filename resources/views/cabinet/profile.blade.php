<x-layouts.shop :footer-categories="$footerCategories" :title="'Профиль — ' . config('app.name')">
    @php $user = auth()->user(); @endphp

    <div class="mx-auto max-w-7xl px-4 py-6">
        <h1 class="text-2xl font-bold text-slate-900">Личный кабинет</h1>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[260px_1fr]">
            <x-cabinet.sidebar active="profile" />

            <div class="space-y-6">
                @if (session('status'))
                    <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Данные профиля --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Данные профиля</h2>
                    <p class="mt-1 text-sm text-slate-500">Имя и email обязательны, остальное — по желанию.</p>

                    <form method="POST" action="{{ route('cabinet.profile.update') }}" class="mt-5 space-y-4">
                        @csrf

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Имя</label>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $user->name) }}"
                                    required
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
                                >
                                @error('name', 'updateProfileInformation')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
                                >
                                @error('email', 'updateProfileInformation')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Телефон</label>
                                <input
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    value="{{ old('phone', $user->phone) }}"
                                    placeholder="+7 (999) 123-45-67"
                                    data-phone-mask
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
                                >
                                @error('phone', 'updateProfileInformation')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="birth_date" class="mb-1 block text-sm font-medium text-slate-700">Дата рождения</label>
                                <input
                                    id="birth_date"
                                    type="date"
                                    name="birth_date"
                                    value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}"
                                    max="{{ now()->format('Y-m-d') }}"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
                                >
                                @error('birth_date', 'updateProfileInformation')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="gender" class="mb-1 block text-sm font-medium text-slate-700">Пол</label>
                                <select
                                    id="gender"
                                    name="gender"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
                                >
                                    <option value="">Не указан</option>
                                    @foreach (\App\Enums\Gender::cases() as $gender)
                                        <option value="{{ $gender->value }}" @selected(old('gender', $user->gender?->value) === $gender->value)>
                                            {{ $gender->getLabel() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('gender', 'updateProfileInformation')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="rounded-md bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                                Сохранить
                            </button>
                        </div>
                    </form>
                </section>

                {{-- Смена пароля --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Смена пароля</h2>

                    <form method="POST" action="{{ route('cabinet.password.update') }}" class="mt-5 space-y-4">
                        @csrf

                        <div>
                            <label for="current_password" class="mb-1 block text-sm font-medium text-slate-700">Текущий пароль</label>
                            <input
                                id="current_password"
                                type="password"
                                name="current_password"
                                required
                                autocomplete="current-password"
                                class="w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
                            >
                            @error('current_password', 'updatePassword')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Новый пароль</label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    required
                                    autocomplete="new-password"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
                                >
                                @error('password', 'updatePassword')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Повторите пароль</label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand-500"
                                >
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="rounded-md bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700">
                                Сменить пароль
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-layouts.shop>
