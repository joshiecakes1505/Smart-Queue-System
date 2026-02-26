<script setup>
import { Head } from '@inertiajs/vue3';

const schoolLogoUrl = document.querySelector('meta[name="app-logo-url"]')?.getAttribute('content')
    || `${window.location.origin}/images/school-logo.png`;

defineProps({
    title: {
        type: String,
        default: 'Smart Queuing System'
    }
});
</script>

<template>
    <div class="min-h-screen bg-white">
        <Head :title="title" />
        
        <!-- Maroon Header Bar -->
        <header class="bg-[#800000] text-white py-4 shadow-sm">
            <div class="container mx-auto px-6">
                <div class="flex items-center gap-3">
                    <img
                        :src="schoolLogoUrl"
                        alt="School Logo"
                        class="h-12 w-12 object-contain"
                    />
                    <div>
                        <h1 class="text-xl font-semibold">Smart School Cashier Queuing System</h1>
                        <p class="text-sm text-yellow-200">Batangas Eastern Colleges</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-gray-50 border-t border-gray-200 py-4 mt-12">
            <div class="container mx-auto px-6 text-center">
                <p class="text-sm text-gray-600">© 2026 Batangas Eastern Colleges</p>
            </div>
        </footer>
    </div>
</template>
