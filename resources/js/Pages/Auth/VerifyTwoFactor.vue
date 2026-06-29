<!-- <script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    two_factor_code: '',
});

const resendForm = useForm({});

const submit = () => {
    form.post(route('two-factor.store'), {
        onFinish: () => form.reset('two_factor_code'),
    });
};

const resendCode = () => {
    resendForm.post(route('two-factor.resend'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Two-Factor Verification" />

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            This system requires Two-Factor Validation. Check your email for a 6-digit confirmation key.
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="two_factor_code" value="Verification Code" />

                <input
                    id="two_factor_code"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                    v-model="form.two_factor_code"
                    required
                    autofocus
                    autocomplete="off"
                />

                <InputError class="mt-2" :message="form.errors.two_factor_code" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <button
                    type="button"
                    @click="resendCode"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 underline mx-4"
                    :disabled="resendForm.processing"
                >
                    Resend Code
                </button>

                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Verify
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template> -->
