<div class="min-h-screen flex">
    <!-- Sección de imagen -->
    <div class="hidden md:flex w-1/2 bg-[#181818] justify-center items-center">
        <img src="{{ asset('image/logos/resistance-logo-two-white.png') }}" alt="Logo Resistance" class="h-48 mx-auto">
    </div>
    <!-- Sección del card de login -->
    <div class="flex w-full md:w-1/2 justify-center items-center bg-[#181818]">
        <div class="w-full max-w-md bg-[#232323] dark:bg-gray-900 p-10 rounded-2xl shadow-2xl border border-[#B0694C]">
            @include('filament.pages.auth.partials.login-header')
            <form wire:submit="authenticate" class="space-y-4">
                <!-- Campo de Usuario -->
                <div>
                    <label for="email" class="block text-sm font-medium text-white mb-2">
                        Usuario <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="email"
                        wire:model="email"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#B0694C] focus:border-transparent"
                        placeholder="Ingresa tu usuario o correo electrónico"
                        required
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Campo de Contraseña -->
                <div>
                    <label for="password" class="block text-sm font-medium text-white mb-2">
                        Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="password"
                        id="password"
                        wire:model="password"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#B0694C] focus:border-transparent"
                        placeholder="Ingresa tu contraseña"
                        required
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <x-filament::button type="submit" class="w-full bg-[#B0694C] hover:bg-[#8a4f32]">
                    Iniciar Sesión
                </x-filament::button>
            </form>
        </div>
    </div>
</div>
