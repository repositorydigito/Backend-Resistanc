<x-filament-panels::page.simple>
    <div class="flex justify-center items-center min-h-screen bg-gradient-to-br from-[#1a1a1a] to-[#2d2d2d]">
        <div class="w-full max-w-md bg-[#232323] dark:bg-gray-900 p-10 rounded-2xl shadow-2xl border border-[#B0694C]">
            <div class="flex flex-col items-center mb-6">
                <img src="{{ asset('image/logos/resistance-logo-two-white.png') }}" alt="Logo Resistance" class="h-16 mb-2">
                <h2 class="text-3xl font-extrabold text-center text-white mb-1 tracking-widest">RESISTANC</h2>
                <p class="text-lg text-center text-[#B0694C] font-semibold mb-2">Entre a su cuenta</p>
                <p class="text-sm text-gray-400 text-center mb-2">¡Bienvenido! Ingresa tus datos para acceder al panel de administración.</p>
            </div>
            <form wire:submit="authenticate" class="space-y-4">
                {{ $this->form }}
                <x-filament::button type="submit" class="w-full bg-[#B0694C] hover:bg-[#8a4f32]">
                    Iniciar Sesión
                </x-filament::button>
            </form>
            <div class="mt-6 text-center text-xs text-gray-500">
                <span>¿Olvidaste tu contraseña? Contacta al administrador.</span>
            </div>
        </div>
    </div>
    <div class="text-center text-xs text-gray-600 mt-4">
        <span>Panel exclusivo para staff de Resistance</span>
    </div>
</x-filament-panels::page.simple>
