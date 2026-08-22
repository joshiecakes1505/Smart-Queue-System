<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <PublicLayout title="Welcome">
        <div class="flex min-h-[70vh] items-center justify-center px-4 py-10 sm:px-6 sm:py-16">
            <div class="w-full max-w-3xl text-center">
                <!-- Large System Title -->
                <h1 class="mb-4 text-4xl font-bold leading-tight text-[#800000] sm:text-5xl md:text-6xl">
                    Less waiting. More serving. Smarter queues.
                </h1>
                
                <!-- Subtitle -->
                <p class="mx-auto mb-10 max-w-xl text-base text-gray-600 sm:mb-12 sm:text-lg md:text-xl">
                    Batangas Eastern Colleges (BEC)
                </p>

                <!-- Primary Buttons -->
                <div class="flex flex-col items-stretch justify-center gap-4 sm:flex-row sm:items-center sm:justify-center">
                    <!-- View Queue Display Button -->
                    <Link
                        :href="route('display.index')"
                        class="w-full rounded-lg bg-[#FFC107] px-8 py-4 text-lg font-semibold text-[#800000] shadow-sm transition hover:bg-[#FFB300] sm:w-64"
                    >
                        View Queue Display
                    </Link>

                    <!-- Staff Login Button -->
                    <Link
                        :href="route('login')"
                        class="w-full rounded-lg bg-[#800000] px-8 py-4 text-lg font-semibold text-white shadow-sm transition hover:bg-[#600000] sm:w-64"
                    >
                     Login
                    </Link>

                </div>

            </div>
        </div>
    </PublicLayout>
</template>
