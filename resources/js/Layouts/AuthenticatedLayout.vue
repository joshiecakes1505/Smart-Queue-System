<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import ChatbotWidget from '@/Components/ChatbotWidget.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    }
});

const logout = () => {
    router.post(route('logout'), { role: roleName.value });
};

const page = usePage();
const schoolLogoUrl = document.querySelector('meta[name="app-logo-url"]')?.getAttribute('content')
    || `${window.location.origin}/images/school-logo.png`;

const roleName = computed(() => page.props.auth?.user?.role_name || null);

const navigationLinks = computed(() => {
    if (roleName.value === 'admin') {
        return [
            { label: 'Admin Dashboard', href: route('admin.dashboard') },
            { label: 'Manage Users', href: route('admin.users.index') },
            { label: 'Service Categories', href: route('admin.service-categories.index') },
            { label: 'Reports', href: route('admin.reports.daily') },
        ];
    }

    if (roleName.value === 'frontdesk') {
        return [
            { label: 'Front Desk Dashboard', href: route('frontdesk.queues.index') },
        ];
    }

    if (roleName.value === 'cashier') {
        return [
            { label: 'Cashier Dashboard', href: route('cashier.index') },
        ];
    }

    return [];
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head :title="title" />
        
        <!-- Maroon Top Navigation Bar -->
        <nav class="bg-[#800000] text-white shadow-sm">
            <div class="container mx-auto px-4 sm:px-6 py-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                    <!-- System Name -->
                    <div class="flex items-center gap-3">
                        <img
                            :src="schoolLogoUrl"
                            alt="School Logo"
                            class="h-11 w-11 object-contain"
                        />
                        <div class="min-w-0">
                            <h1 class="text-base sm:text-lg font-semibold leading-tight">Smart Cashier Queuing System - BEC</h1>
                            <p class="text-xs text-yellow-200">{{ title }}</p>
                        </div>
                    </div>

                    <!-- Navigation Links -->
                    <div class="order-2 mt-1 flex flex-wrap gap-2 border-t border-white/20 pt-3 sm:order-none sm:col-span-2 sm:mt-0 sm:border-t-0 sm:pt-0">
                        <Link
                            v-for="link in navigationLinks"
                            :key="link.href"
                            :href="link.href"
                            class="rounded-lg bg-white/10 px-3 py-1.5 text-sm transition hover:bg-white/20"
                        >
                            {{ link.label }}
                        </Link>

                        <Link
                            :href="route('display.index')"
                            class="rounded-lg bg-[#FFC107] px-3 py-1.5 text-sm text-[#800000] transition hover:bg-[#FFB300]"
                        >
                            Display Board
                        </Link>
                    </div>

                    <!-- User Info & Logout -->
                    <div class="order-3 flex w-full flex-col gap-3 sm:order-none sm:col-start-2 sm:row-start-1 sm:w-auto sm:flex-row sm:items-center sm:justify-end sm:gap-4">
                        <span class="text-sm break-all">{{ $page.props.auth.user.name }}</span>
                        <button
                            @click="logout"
                            class="w-full rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-[#800000] transition hover:bg-yellow-600 sm:w-auto"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container mx-auto px-4 sm:px-6 py-6 sm:py-8">
            <slot />
        </main>

        <ChatbotWidget v-if="roleName === 'admin'" />
    </div>
</template>
