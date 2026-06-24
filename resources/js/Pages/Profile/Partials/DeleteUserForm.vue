<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import ProfileSection from '@/Components/UI/ProfileSection.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <ProfileSection
        title="Hapus akun"
        description="Setelah dihapus, semua data akun akan dihapus permanen. Pastikan Anda sudah menyimpan data penting."
        danger
    >
        <DangerButton @click="confirmUserDeletion">Hapus akun saya</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">
                    Yakin ingin menghapus akun?
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Masukkan password untuk mengonfirmasi penghapusan permanen akun Anda.
                </p>

                <div class="mt-6">
                    <InputLabel for="password" value="Password" class="sr-only" />
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 max-w-md"
                        placeholder="Password Anda"
                        @keyup.enter="deleteUser"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                    <DangerButton :disabled="form.processing" @click="deleteUser">
                        Hapus akun
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </ProfileSection>
</template>
