<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This system requires Two-Factor Validation. Check your email for a 6-digit confirmation key.') }}
    </div>

    <!-- Session Status -->
    <x-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('two-factor.store') }}">
        @csrf
        <!-- Code Input -->
        <div>
            <x-input-label for="two_factor_code" :value="__('Verification Code')" />
            <x-text-input id="two_factor_code" class="block mt-1 w-full" type="text" name="two_factor_code" required autofocus autocomplete="off" />
            <x-input-error :messages="$errors->get('two_factor_code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <button form="resend-form" type="submit" class="text-sm text-gray-600 hover:text-gray-900 underline mx-4">
                {{ __('Resend Code') }}
            </button>
            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>

    <form id="resend-form" method="POST" action="{{ route('two-factor.resend') }}" class="hidden">
        @csrf
    </form>
</x-guest-layout>