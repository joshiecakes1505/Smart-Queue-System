<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const twoFactorEnabled = computed(() => !!page.props.auth?.user?.two_factor_enabled);

const processing = ref(false);
const justSaved = ref(false);

const toggleTwoFactor = () => {
    processing.value = true;
    justSaved.value = false;

    const routeName = twoFactorEnabled.value
        ? 'profile.two-factor.disable'
        : 'profile.two-factor.enable';

    router.patch(route(routeName), {}, {
        preserveScroll: true,
        onSuccess: () => {
            justSaved.value = true;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Two-Factor Authentication
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                When enabled, you'll be asked to verify a code sent to your email each time you log in.
            </p>
        </header>

        <div class="mt-6 flex items-center gap-4">
            <span
                class="text-sm font-semibold"
                :class="twoFactorEnabled ? 'text-green-700' : 'text-red-700'"
            >
                {{ twoFactorEnabled ? 'Enabled' : 'Disabled' }}
            </span>

            <SecondaryButton
                v-if="twoFactorEnabled"
                :disabled="processing"
                @click="toggleTwoFactor"
            >
                Disable 2FA
            </SecondaryButton>
            <PrimaryButton
                v-else
                :disabled="processing"
                @click="toggleTwoFactor"
            >
                Enable 2FA
            </PrimaryButton>

            <Transition
                enter-active-class="transition ease-in-out"
                enter-from-class="opacity-0"
                leave-active-class="transition ease-in-out"
                leave-to-class="opacity-0"
            >
                <p v-if="justSaved" class="text-sm text-gray-600">
                    Saved.
                </p>
            </Transition>
        </div>
    </section>
</template>
