<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3'

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Login - Smart Queuing System" />
    <div class="min-h-screen bg-white">
        <!-- Maroon Header -->
        <header class="bg-[#800000] text-white py-6">
            <div class="container mx-auto px-6 text-center">
                <h1 class="text-3xl font-bold">Smart Queuing System</h1>
                <p class="text-yellow-200 mt-1">Batangas Eastern Colleges</p>
            </div>
        </header>

        <!-- Login Card Container -->
        <div class="flex items-center justify-center py-12 px-4">
            <div class="w-full max-w-md">
                <!-- Login Card -->
                <div class="bg-white border-2 border-gray-200 rounded-lg shadow-sm p-8">
                    <!-- Title -->
                    <h2 class="text-center text-2xl font-bold text-[#800000] mb-2">
                        Staff Login
                    </h2>
                    <p class="text-center text-gray-600 mb-6 text-sm">
                        For authorized personnel only
                    </p>

                    <!-- Status Message -->
                    <div v-if="status" class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-lg">
                        {{ status }}
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000] focus:border-[#800000]"
                                placeholder="Enter your email"
                                required
                                autofocus
                            />
                            <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">
                                {{ form.errors.email }}
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input
                                v-model="form.password"
                                type="password"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000] focus:border-[#800000]"
                                placeholder="Enter your password"
                                required
                            />
                            <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">
                                {{ form.errors.password }}
                            </div>
                        </div>

                        <!-- Remember & Forgot Password -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="rounded border-gray-300 text-[#800000] focus:ring-[#800000]"
                                />
                                <span class="text-sm ml-2 text-gray-700">Remember me</span>
                            </label>
                            
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
                            class="w-full bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] py-3 rounded-lg font-semibold transition disabled:opacity-50"
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
        <footer class="bg-gray-50 border-t border-gray-200 py-4 absolute bottom-0 w-full">
            <div class="container mx-auto px-6 text-center">
                <p class="text-sm text-gray-600">© 2026 Batangas Eastern Colleges</p>
            </div>
        </footer>
    </div>
</template>
