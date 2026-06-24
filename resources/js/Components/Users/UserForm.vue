<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    formOptions: { type: Object, required: true },
    isEdit: { type: Boolean, default: false },
});

const roleOptions = computed(() =>
    (props.formOptions.roles ?? []).map((r) => ({
        value: r.id,
        label: r.name,
    }))
);

const divisionOptions = computed(() =>
    (props.formOptions.divisions ?? []).map((d) => ({
        value: d.id,
        label: d.name,
    }))
);

const departmentOptions = computed(() => {
    if (!props.form.division_id) {
        return [];
    }
    const division = props.formOptions.divisions?.find(
        (d) => d.id === Number(props.form.division_id)
    );
    return (division?.departments ?? []).map((dept) => ({
        value: dept.id,
        label: dept.name,
    }));
});

watch(
    () => props.form.division_id,
    () => {
        if (
            props.form.department_id &&
            !departmentOptions.value.some(
                (d) => d.value === Number(props.form.department_id)
            )
        ) {
            props.form.department_id = '';
        }
    }
);
</script>

<template>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="space-y-4">
            <div>
                <InputLabel for="name" value="Nama lengkap *" />
                <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email *" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel
                    for="password"
                    :value="isEdit ? 'Password baru (opsional)' : 'Password *'"
                />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    :required="!isEdit"
                    autocomplete="new-password"
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div v-if="!isEdit || form.password">
                <InputLabel for="password_confirmation" value="Konfirmasi password" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <InputLabel value="Role *" />
                <SearchableSelect
                    v-model="form.role_id"
                    :options="roleOptions"
                    placeholder="Pilih role"
                    class="mt-1"
                />
                <InputError class="mt-1" :message="form.errors.role_id" />
            </div>

            <div>
                <InputLabel value="Divisi" />
                <SearchableSelect
                    v-model="form.division_id"
                    :options="divisionOptions"
                    placeholder="Pilih divisi"
                    class="mt-1"
                    can-clear
                />
            </div>

            <div>
                <InputLabel value="Departemen" />
                <SearchableSelect
                    v-model="form.department_id"
                    :options="departmentOptions"
                    placeholder="Pilih departemen"
                    class="mt-1"
                    :disabled="!form.division_id"
                    can-clear
                />
                <InputError class="mt-1" :message="form.errors.department_id" />
            </div>

            <div>
                <InputLabel for="position" value="Jabatan" />
                <TextInput id="position" v-model="form.position" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.position" />
            </div>

            <div>
                <InputLabel for="phone" value="Telepon" />
                <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                <InputError class="mt-1" :message="form.errors.phone" />
            </div>

            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.is_active" name="is_active" />
                <span class="text-sm text-slate-700">User aktif</span>
            </label>
        </div>
    </div>
</template>
