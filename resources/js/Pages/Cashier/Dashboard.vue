<script setup>
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
  window: Object,
  current: Object,
  next: Array,
})
</script>

<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold">Cashier Dashboard</h1>
    <div class="mt-4">
      <p>Window: {{ window?.name ?? 'Unassigned' }}</p>
      <p>Now Serving: {{ current?.queue_number ?? '—' }}</p>
      <p>Next: <span v-if="next.length">{{ next[0].queue_number }}</span></p>
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

   <div class="p-4">
    <h1 class="text-2xl font-bold">Cashier</h1>

    <div class="mt-4">
      <p><strong>Window:</strong> {{ window?.name ?? 'Unassigned' }}</p>
      <p class="mt-2"><strong>Now Serving:</strong> {{ current?.queue_number ?? '—' }}</p>

      <h3 class="mt-4">Next</h3>
      <ul>
        <li v-for="q in next" :key="q.id">{{ q.queue_number }} — {{ q.client_name ?? 'Guest' }}</li>
      </ul>
    </div>
  </div>

  <div class="p-4">
    <h2 class="text-lg">{{ window.name }}</h2>
    <div class="mt-2">
      <p>Current: {{ queue?.queue_number ?? '—' }}</p>
    </div>
  </div>
</template>
