<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import ProfileSection from '@/Components/UI/ProfileSection.vue';
import TextInput from '@/Components/TextInput.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import { CameraIcon, TrashIcon } from '@heroicons/vue/24/outline';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;
const fileInput = ref(null);
const previewUrl = ref(null);

const form = useForm({
    name: user.name,
    email: user.email,
    avatar: null,
    remove_avatar: false,
});

const displayAvatarUrl = computed(() => {
    if (previewUrl.value) return previewUrl.value;
    if (form.remove_avatar) return null;
    return user.avatar_url ?? null;
});

const onAvatarSelected = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }

    form.avatar = file;
    form.remove_avatar = false;
    previewUrl.value = URL.createObjectURL(file);
};

const removeAvatar = () => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }

    form.avatar = null;
    form.remove_avatar = true;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    const options = {
        onError: (errors) => notifyValidationErrors(errors),
        preserveScroll: true,
    };

    if (form.avatar || form.remove_avatar) {
        options.forceFormData = true;
    }

    form.patch(route('profile.update'), options);
};

onBeforeUnmount(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <ProfileSection
        title="Informasi profil"
        description="Perbarui foto, nama, dan alamat email akun Anda."
    >
        <form class="max-w-xl space-y-6" @submit.prevent="submit">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <Avatar
                    :name="form.name || user.name"
                    :initials="user.initials"
                    :src="displayAvatarUrl"
                    size="xl"
                />
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-800">Foto profil</p>
                    <p class="text-xs text-slate-500">
                        JPG, PNG, atau WebP. Maks. 2 MB.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            @change="onAvatarSelected"
                        />
                        <button
                            type="button"
                            class="btn-secondary inline-flex items-center gap-1.5 text-sm"
                            @click="fileInput?.click()"
                        >
                            <CameraIcon class="h-4 w-4" />
                            {{ displayAvatarUrl ? 'Ganti foto' : 'Unggah foto' }}
                        </button>
                        <button
                            v-if="displayAvatarUrl"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                            @click="removeAvatar"
                        >
                            <TrashIcon class="h-4 w-4" />
                            Hapus foto
                        </button>
                    </div>
                    <InputError :message="form.errors.avatar" />
                </div>
            </div>

            <div>
                <InputLabel for="name" value="Nama lengkap" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div
                v-if="mustVerifyEmail && user.email_verified_at === null"
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                Email belum diverifikasi.
                <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="font-semibold text-brand-600 hover:underline"
                >
                    Kirim ulang email verifikasi
                </Link>
                <p
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 font-medium text-emerald-700"
                >
                    Link verifikasi telah dikirim.
                </p>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Simpan perubahan</PrimaryButton>
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
                        Tersimpan.
                    </span>
                </Transition>
            </div>
        </form>
    </ProfileSection>
</template>
