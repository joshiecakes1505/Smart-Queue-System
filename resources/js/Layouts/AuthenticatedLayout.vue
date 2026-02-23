<script setup>
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    }
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head :title="title" />
        
        <!-- Maroon Top Navigation Bar -->
        <nav class="bg-[#800000] text-white shadow-sm">
            <div class="container mx-auto px-6 py-4">
                <div class="flex justify-between items-center">
                    <!-- System Name -->
                    <div>
                        <h1 class="text-lg font-semibold">Smart Queuing System - BEC</h1>
                        <p class="text-xs text-yellow-200">{{ title }}</p>
                    </div>

                    <!-- User Info & Logout -->
                    <div class="flex items-center space-x-4">
                        <span class="text-sm">{{ $page.props.auth.user.name }}</span>
                        <button
                            @click="logout"
                            class="bg-yellow-500 hover:bg-yellow-600 text-[#800000] px-4 py-2 rounded-lg text-sm font-medium transition"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container mx-auto px-6 py-8">
            <slot />
        </main>
    </div>
</template>
