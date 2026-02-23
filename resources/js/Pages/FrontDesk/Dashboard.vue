<script setup>
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';


defineProps({
  todayTotal: Number,
  waitingCount: Number,
  serviceCategories: Array,
})
</script>

<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold">Front Desk — Dashboard</h1>
    <div class="mt-4">
      <p>Today total: {{ todayTotal ?? 0 }}</p>
      <p>Waiting: {{ waitingCount ?? 0 }}</p>
    </div>
  </div>

  <div class="mt-3 space-y-1">
            <ResponsiveNavLink
                :href="route('logout')"
                method="post"
                as="button"
            >
                Log Out
            </ResponsiveNavLink>
        </div>
</template>
