<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PasswordStrengthMeter from '@/Components/UI/PasswordStrengthMeter.vue';
import ProfileSection from '@/Components/UI/ProfileSection.vue';
import TextInput from '@/Components/TextInput.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const passwordsMatch = computed(() => {
    if (!form.password_confirmation) return null;
    return form.password === form.password_confirmation;
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors) => {
            if (Object.keys(errors).length) notifyValidationErrors(errors);
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <ProfileSection
        title="Ubah password"
        description="Gunakan password yang kuat dan unik untuk keamanan akun."
    >
        <form class="max-w-xl space-y-5" @submit.prevent="updatePassword">
            <div>
                <InputLabel for="current_password" value="Password saat ini" />
                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1"
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.current_password" />
            </div>

            <div>
                <InputLabel for="password" value="Password baru" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1"
                    autocomplete="new-password"
                />
                <PasswordStrengthMeter :password="form.password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Konfirmasi password baru" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1"
                    autocomplete="new-password"
                />
                <p
                    v-if="passwordsMatch === true"
                    class="mt-2 text-xs font-medium text-emerald-600"
                >
                    Password cocok.
                </p>
                <p
                    v-else-if="passwordsMatch === false"
                    class="mt-2 text-xs font-medium text-rose-600"
                >
                    Password tidak cocok.
                </p>
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Simpan password</PrimaryButton>
                <Transition
                    enter-active-class="transition ease-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in"
                    leave-to-class="opacity-0"
                >
                    <span
                        v-if="form.recentlySuccessful"
                        class="text-sm font-medium text-emerald-600"
                    >
                        Password diperbarui.
                    </span>
                </Transition>
            </div>
        </form>
    </ProfileSection>
</template>
