<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/UI/TextareaInput.vue';

defineProps({
    form: { type: Object, required: true },
    formOptions: { type: Object, required: true },
    isSystem: { type: Boolean, default: false },
});

const togglePermission = (form, key, checked) => {
    const list = [...(form.permissions || [])];
    if (checked) {
        if (!list.includes(key)) {
            list.push(key);
        }
    } else {
        const idx = list.indexOf(key);
        if (idx >= 0) {
            list.splice(idx, 1);
        }
    }
    form.permissions = list;
};
</script>

<template>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="space-y-4">
            <div>
                <InputLabel for="name" value="Nama role *" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="slug" value="Slug *" />
                <TextInput
                    id="slug"
                    v-model="form.slug"
                    class="mt-1 block w-full font-mono text-sm"
                    :disabled="isSystem"
                    placeholder="otomatis dari nama jika kosong"
                />
                <p v-if="isSystem" class="mt-1 text-xs text-slate-500">
                    Slug role sistem tidak dapat diubah.
                </p>
                <InputError class="mt-1" :message="form.errors.slug" />
            </div>

            <div>
                <InputLabel for="description" value="Deskripsi" />
                <TextareaInput
                    id="description"
                    v-model="form.description"
                    :rows="3"
                    class="mt-1"
                />
                <InputError class="mt-1" :message="form.errors.description" />
            </div>

            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.is_active" name="is_active" />
                <span class="text-sm text-slate-700">Role aktif</span>
            </label>
        </div>

        <div>
            <InputLabel value="Permission" />
            <p class="mb-3 text-xs text-slate-500">
                Hak akses tambahan (opsional). Akses utama masih mengikuti slug role.
            </p>
            <div class="space-y-2 rounded-xl border border-slate-100 bg-slate-50/50 p-4">
                <label
                    v-for="perm in formOptions.permissionOptions"
                    :key="perm.key"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-white"
                >
                    <Checkbox
                        :checked="form.permissions?.includes(perm.key)"
                        @update:checked="(val) => togglePermission(form, perm.key, val)"
                    />
                    <span class="text-sm text-slate-700">{{ perm.label }}</span>
                </label>
            </div>
            <InputError class="mt-1" :message="form.errors.permissions" />
        </div>
    </div>
</template>
