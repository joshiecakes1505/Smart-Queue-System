<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
    email: {
        type: String,
        required: true,
    },
});

const form = useForm({
    two_factor_code: '',
});

const resendForm = useForm({});

const submit = () => {
    form.post(route('password.two-factor.store'), {
        onFinish: () => form.reset('two_factor_code'),
    });
};

const resendCode = () => {
    resendForm.post(route('password.two-factor.resend'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Password Reset Verification" />

        <div class="mb-4 text-sm text-gray-600">
            We sent a verification code to {{ email }}. Enter the code before we
            send your password reset link.
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="two_factor_code" value="Verification Code" />

                <input
                    id="two_factor_code"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                    class="text-sm text-gray-600 hover:text-gray-900 underline mx-4"
                    :disabled="resendForm.processing"
                >
                    Resend Code
                </button>

                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Verify and Continue
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>