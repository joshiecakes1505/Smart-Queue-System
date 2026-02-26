<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

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
            { label: 'Daily Reports', href: route('admin.reports.daily') },
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
            <div class="container mx-auto px-6 py-4">
                <div class="flex justify-between items-center">
                    <!-- System Name -->
                    <div class="flex items-center gap-3">
                        <img
                            :src="schoolLogoUrl"
                            alt="School Logo"
                            class="h-11 w-11 object-contain"
                        />
                        <div>
                            <h1 class="text-lg font-semibold">Smart Queuing System - BEC</h1>
                            <p class="text-xs text-yellow-200">{{ title }}</p>
                        </div>
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

                <div class="mt-4 pt-3 border-t border-white/20 flex flex-wrap gap-2">
                    <Link
                        v-for="link in navigationLinks"
                        :key="link.href"
                        :href="link.href"
                        class="px-3 py-1.5 rounded-lg text-sm bg-white/10 hover:bg-white/20 transition"
                    >
                        {{ link.label }}
                    </Link>

                    <Link
                        :href="route('display.index')"
                        class="px-3 py-1.5 rounded-lg text-sm bg-[#FFC107] text-[#800000] hover:bg-[#FFB300] transition"
                    >
                        Display Board
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container mx-auto px-6 py-8">
            <slot />
        </main>
    </div>
</template>
