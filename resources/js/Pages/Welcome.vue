<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import { onBeforeUnmount, ref } from "vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const eyeIconUrl = `${window.location.origin}/images/eye.png`;

const showPassword = ref(false);
let passwordTimer = null;

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;

    if (showPassword.value) {
        clearTimeout(passwordTimer);
        passwordTimer = setTimeout(() => {
            showPassword.value = false;
        }, 300);
    } else if (passwordTimer) {
        clearTimeout(passwordTimer);
    }
};

onBeforeUnmount(() => {
    if (passwordTimer) {
        clearTimeout(passwordTimer);
    }
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Login - Smart Queuing System" />
    <div class="min-h-screen bg-white flex flex-col">
        <!-- Maroon Header -->
        <header class="bg-[#800000] text-white py-6">
            <div class="container mx-auto px-6 text-center">
                <h1 class="text-3xl font-bold">Smart Cashier Queuing System</h1>
                <p class="text-yellow-200 mt-1">Batangas Eastern Colleges</p>
            </div>
        </header>

        <!-- Login Card Container -->
        <div class="flex-1 flex items-center justify-center py-8 sm:py-12 px-4">
            <div class="w-full max-w-md">
                <!-- Login Card -->
                <div
                    class="bg-white border-2 border-gray-200 rounded-lg shadow-sm p-8"
                >
                    <!-- Title -->
                    <h2
                        class="text-center text-2xl font-bold text-[#800000] mb-2"
                    >
                        Login
                    </h2>
                    <p class="text-center text-gray-600 mb-6 text-sm">
                        For authorized personnel only
                    </p>

                    <!-- Status Message -->
                    <div
                        v-if="status"
                        class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg"
                    >
                        {{ status }}
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Email -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Email</label
                            >
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000] focus:border-[#800000]"
                                placeholder="Enter your email"
                                required
                                autofocus
                            />
                            <div
                                v-if="form.errors.email"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.email }}
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-2"
                                >Password</label
                            >
                            <div class="relative">
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-12 focus:outline-none focus:ring-2 focus:ring-[#800000] focus:border-[#800000]"
                                    placeholder="Enter your password"
                                    required
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-[#800000] focus:outline-none"
                                    :aria-pressed="showPassword"
                                    :aria-label="
                                        showPassword
                                            ? 'Hide password'
                                            : 'Show password'
                                    "
                                    @click="togglePasswordVisibility"
                                >
                                    <img
                                        :src="eyeIconUrl"
                                        alt="Toggle password"
                                        class="h-5 w-5"
                                    />
                                </button>
                            </div>
                            <div
                                v-if="form.errors.password"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ form.errors.password }}
                            </div>
                        </div>

                        <!-- Remember & Forgot Password -->
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm text-[#800000] hover:text-[#600000] underline"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <!-- Login Button -->
                        <button
                            type="submit"
                            class="w-full rounded-lg bg-[#FFC107] py-3 font-semibold text-[#800000] transition hover:bg-[#FFB300] disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Logging in...</span>
                            <span v-else>Login</span>
                        </button>
                    </form>

                    <!-- Back to Landing -->
                    <div class="mt-6 text-center">
                        <Link
                            :href="route('landing')"
                            class="text-sm text-gray-600 hover:text-[#800000]"
                        >
                            ← Back to Home
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
