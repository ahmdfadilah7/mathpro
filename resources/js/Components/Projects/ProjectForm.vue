<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import RichTextEditor from '@/Components/UI/RichTextEditor.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    formOptions: { type: Object, required: true },
    isEdit: { type: Boolean, default: false },
});

const departments = computed(() => {
    const division = props.formOptions.divisions?.find(
        (d) => d.id === Number(props.form.division_id)
    );
    return division?.departments ?? [];
});

const divisionOptions = computed(() =>
    (props.formOptions.divisions ?? []).map((d) => ({
        value: d.id,
        label: d.name,
    }))
);

const departmentOptions = computed(() =>
    departments.value.map((d) => ({
        value: d.id,
        label: d.name,
    }))
);

const managerOptions = computed(() =>
    (props.formOptions.managers ?? []).map((m) => ({
        value: m.id,
        label: m.name,
    }))
);

const statusOptions = computed(() =>
    (props.formOptions.statuses ?? []).map((s) => ({
        value: s.value,
        label: s.label,
    }))
);

const priorityOptions = computed(() =>
    (props.formOptions.priorities ?? []).map((p) => ({
        value: p.value,
        label: p.label,
    }))
);

watch(
    () => props.form.division_id,
    () => {
        const deptIds = departments.value.map((d) => d.id);
        if (props.form.department_id && !deptIds.includes(Number(props.form.department_id))) {
            props.form.department_id = '';
        }
    }
);

const generateCode = () => {
    const dept = departments.value.find((d) => d.id === Number(props.form.department_id));
    const prefix = dept?.code ?? 'PRJ';
    const rand = Math.random().toString(36).substring(2, 6).toUpperCase();
    props.form.code = `PRJ-${prefix}-${rand}`;
};

// Pastikan progress 0 saat project baru
if (!props.isEdit && (props.form.progress === '' || props.form.progress == null)) {
    props.form.progress = 0;
}
</script>

<template>
    <div class="space-y-8">
        <section>
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-400">
                Informasi dasar
            </h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <InputLabel for="name" value="Nama project *" />
                    <TextInput id="name" v-model="form.name" class="mt-1" placeholder="Contoh: ERP Modernization" />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="code" value="Kode project *" />
                    <div class="mt-1 flex gap-2">
                        <TextInput
                            id="code"
                            v-model="form.code"
                            class="flex-1 font-mono uppercase"
                            placeholder="PRJ-DEV-001"
                            :disabled="isEdit"
                        />
                        <button
                            v-if="!isEdit"
                            type="button"
                            class="btn-secondary shrink-0 whitespace-nowrap text-xs"
                            @click="generateCode"
                        >
                            Generate
                        </button>
                    </div>
                    <InputError class="mt-1" :message="form.errors.code" />
                </div>

                <div>
                    <InputLabel for="color" value="Warna identitas *" />
                    <div class="mt-1 flex items-center gap-3">
                        <input
                            id="color"
                            v-model="form.color"
                            type="color"
                            class="h-10 w-14 cursor-pointer rounded-lg border border-slate-200"
                        />
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="preset in formOptions.colorPresets"
                                :key="preset"
                                type="button"
                                class="h-7 w-7 rounded-lg ring-2 ring-offset-1 transition hover:scale-110"
                                :class="form.color === preset ? 'ring-brand-400' : 'ring-transparent'"
                                :style="{ backgroundColor: preset }"
                                @click="form.color = preset"
                            />
                        </div>
                    </div>
                    <InputError class="mt-1" :message="form.errors.color" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="description" value="Deskripsi" />
                    <div class="mt-1">
                        <RichTextEditor v-model="form.description" />
                    </div>
                    <InputError class="mt-1" :message="form.errors.description" />
                </div>
            </div>
        </section>

        <section>
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-400">
                Organisasi
            </h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <InputLabel for="division_id" value="Divisi *" />
                    <SearchableSelect
                        id="division_id"
                        v-model="form.division_id"
                        class="mt-1"
                        :options="divisionOptions"
                        placeholder="Cari divisi..."
                    />
                    <InputError class="mt-1" :message="form.errors.division_id" />
                </div>

                <div>
                    <InputLabel for="department_id" value="Departemen *" />
                    <SearchableSelect
                        id="department_id"
                        v-model="form.department_id"
                        class="mt-1"
                        :options="departmentOptions"
                        :disabled="!form.division_id"
                        placeholder="Cari departemen..."
                    />
                    <InputError class="mt-1" :message="form.errors.department_id" />
                </div>

                <div class="sm:col-span-2">
                    <InputLabel for="manager_id" value="Project manager" />
                    <SearchableSelect
                        id="manager_id"
                        v-model="form.manager_id"
                        class="mt-1"
                        :options="managerOptions"
                        :can-clear="true"
                        placeholder="Cari project manager..."
                    />
                    <InputError class="mt-1" :message="form.errors.manager_id" />
                </div>
            </div>
        </section>

        <section>
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-400">
                Status & timeline
            </h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <InputLabel for="status" value="Status *" />
                    <SearchableSelect
                        id="status"
                        v-model="form.status"
                        class="mt-1"
                        :options="statusOptions"
                        :searchable="true"
                        placeholder="Pilih status..."
                    />
                    <InputError class="mt-1" :message="form.errors.status" />
                </div>

                <div>
                    <InputLabel for="priority" value="Prioritas *" />
                    <SearchableSelect
                        id="priority"
                        v-model="form.priority"
                        class="mt-1"
                        :options="priorityOptions"
                        placeholder="Pilih prioritas..."
                    />
                    <InputError class="mt-1" :message="form.errors.priority" />
                </div>

                <div v-if="isEdit">
                    <InputLabel for="progress" value="Progress (%) *" />
                    <div class="mt-1 flex items-center gap-3">
                        <input
                            id="progress"
                            v-model.number="form.progress"
                            type="range"
                            min="0"
                            max="100"
                            class="h-2 flex-1 cursor-pointer appearance-none rounded-full bg-slate-200 accent-brand-600"
                        />
                        <span class="w-10 text-right text-sm font-bold text-brand-600">
                            {{ form.progress ?? 0 }}%
                        </span>
                    </div>
                    <InputError class="mt-1" :message="form.errors.progress" />
                </div>

                <div v-else class="flex items-end">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        Progress dimulai dari
                        <span class="font-bold text-brand-600">0%</span>
                        (diperbarui setelah project berjalan)
                    </div>
                </div>

                <div>
                    <InputLabel for="budget" value="Budget (Rp)" />
                    <TextInput
                        id="budget"
                        v-model="form.budget"
                        type="number"
                        min="0"
                        class="mt-1"
                        placeholder="0"
                    />
                    <InputError class="mt-1" :message="form.errors.budget" />
                </div>

                <div>
                    <InputLabel for="start_date" value="Tanggal mulai" />
                    <TextInput id="start_date" v-model="form.start_date" type="date" class="mt-1" />
                    <InputError class="mt-1" :message="form.errors.start_date" />
                </div>

                <div>
                    <InputLabel for="due_date" value="Target selesai" />
                    <TextInput id="due_date" v-model="form.due_date" type="date" class="mt-1" />
                    <InputError class="mt-1" :message="form.errors.due_date" />
                </div>
            </div>
        </section>
    </div>
</template>
