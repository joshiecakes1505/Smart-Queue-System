<script setup>
import { Link } from '@inertiajs/vue3';

const schoolLogoUrl = document.querySelector('meta[name="app-logo-url"]')?.getAttribute('content')
    || `${window.location.origin}/images/school-logo.png`;
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 py-6 sm:px-6 sm:py-8"
    >
        <div>
            <Link href="/">
                <img
                    :src="schoolLogoUrl"
                    alt="School Logo"
                    class="h-20 w-20 object-contain"
                />
            </Link>
        </div>

        <div
            class="mt-6 w-full max-w-md overflow-hidden bg-white px-5 py-4 shadow-md sm:rounded-lg sm:px-6"
        >
            <slot />
        </div>
    </div>
</template>
