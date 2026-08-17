<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    status: {
        type: String,
    },
});

const page = usePage();
const userEmail = computed(() => page.props.auth?.user?.email ?? '');

const form = useForm({
    two_factor_code: '',
});

const resendForm = useForm({});

const submit = () => {
    form.post(route('two-factor.store'), {
        onFinish: () => form.reset('two_factor_code'),
    });
};

const resendCode = () => {
    resendForm.post(route('two-factor.resend'));
};

const onCodeInput = (event) => {
    form.two_factor_code = event.target.value.replace(/\D/g, '').slice(0, 6);
};
</script>

<template>
    <Head title="Verify Your Identity" />
    <div class="min-h-screen bg-white flex flex-col">
        <!-- Maroon Header -->
        <header class="bg-[#800000] text-white py-6">
            <div class="container mx-auto px-6 text-center">
                <h1 class="text-3xl font-bold">Smart Cashier Queuing System</h1>
                <p class="text-yellow-200 mt-1">Batangas Eastern Colleges</p>
            </div>
        </header>

        <!-- Verification Card Container -->
        <div class="flex-1 flex items-center justify-center py-8 sm:py-12 px-4">
            <div class="w-full max-w-md">
                <div class="bg-white border-2 border-gray-200 rounded-lg shadow-sm p-8">
                    <!-- Title -->
                    <h2 class="text-center text-2xl font-bold text-[#800000] mb-2">
                        Verify Your Identity
                    </h2>
                    <p class="text-center text-gray-600 mb-1 text-sm">
                        This is just a quick security check.
                    </p>
                    <p class="text-center text-gray-600 mb-6 text-sm">
                        We sent a 6-digit code
                        <span v-if="userEmail" class="font-medium text-gray-800">to {{ userEmail }}</span>
                        <span v-else>to your email</span>
                        — it's valid for 10 minutes.
                    </p>

                    <!-- Status Message -->
                    <div
                        v-if="status"
                        class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg text-center"
                    >
                        {{ status }}
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <label
                                for="two_factor_code"
                                class="block text-sm font-medium text-gray-700 mb-2 text-center"
                            >
                                Enter the 6-digit code
                            </label>
                            <input
                                id="two_factor_code"
                                :value="form.two_factor_code"
                                @input="onCodeInput"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-2xl tracking-[0.5em] font-semibold focus:outline-none focus:ring-2 focus:ring-[#800000] focus:border-[#800000]"
                                placeholder="000000"
                                required
                                autofocus
                            />
                            <div
                                v-if="form.errors.two_factor_code"
                                class="text-red-500 text-sm mt-2 text-center"
                            >
                                {{ form.errors.two_factor_code }}
                            </div>
                        </div>

                        <!-- Verify Button -->
                        <button
                            type="submit"
                            class="w-full rounded-lg bg-[#FFC107] py-3 font-semibold text-[#800000] transition hover:bg-[#FFB300] disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Verifying...</span>
                            <span v-else>Verify &amp; Continue</span>
                        </button>
                    </form>

                    <!-- Didn't get it? -->
                    <div class="mt-5 text-center text-sm text-gray-600">
                        Didn't get a code?
                        <button
                            type="button"
                            @click="resendCode"
                            class="font-medium text-[#800000] hover:text-[#600000] underline disabled:opacity-50"
                            :disabled="resendForm.processing"
                        >
                            {{ resendForm.processing ? 'Sending...' : 'Resend it' }}
                        </button>
                    </div>

                    <!-- Wrong account -->
                    <div class="mt-6 text-center border-t border-gray-100 pt-4">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-sm text-gray-600 hover:text-[#800000]"
                        >
                            Not you? Log out and start over
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-auto border-t border-gray-200 bg-gray-50 py-4">
            <div class="container mx-auto px-6 text-center">
                <p class="text-sm text-gray-600">
                    © 2026 Batangas Eastern Colleges
                </p>
            </div>
        </footer>
    </div>
</template>
