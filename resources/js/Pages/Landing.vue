<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <PublicLayout title="Welcome">
        <div class="flex items-center justify-center min-h-[70vh]">
            <div class="text-center px-6">
                <!-- Large System Title -->
                <h1 class="text-4xl md:text-5xl font-bold text-[#800000] mb-4">
                    Smart School Cashier<br />Queuing System
                </h1>
                
                <!-- Subtitle -->
                <p class="text-xl text-gray-600 mb-12">
                    Batangas Eastern Colleges (BEC)
                </p>

                <!-- Primary Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <!-- View Queue Display Button -->
                    <Link
                        :href="route('display.index')"
                        class="w-64 bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-8 py-4 rounded-lg text-lg font-semibold transition shadow-sm"
                    >
                        View Queue Display
                    </Link>

                    <!-- Staff Login Button -->
                    <Link
                        :href="route('login')"
                        class="w-64 bg-[#800000] hover:bg-[#600000] text-white px-8 py-4 rounded-lg text-lg font-semibold transition shadow-sm"
                    >
                        Staff Login
                    </Link>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
