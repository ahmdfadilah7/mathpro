<script setup>
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { InformationCircleIcon, PlusIcon, TrashIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    project: { type: Object, required: true },
    members: { type: Array, default: () => [] },
    availableUsers: { type: Array, default: () => [] },
    memberAccessOptions: { type: Array, default: () => [] },
    permissions: { type: Object, required: true },
});

const form = useForm({
    members: props.members.map((m) => ({
        user_id: m.user_id,
        access: m.access,
    })),
});

const userOptions = computed(() =>
    props.availableUsers.map((u) => ({
        value: u.id,
        label: u.name,
    }))
);

const accessOptions = computed(() =>
    props.memberAccessOptions.map((a) => ({
        value: a.value,
        label: a.label,
    }))
);

const usedUserIds = computed(() =>
    form.members.map((m) => Number(m.user_id)).filter(Boolean)
);

const addMemberRow = () => {
    form.members.push({ user_id: '', access: 'viewer' });
};

const removeMemberRow = (index) => {
    form.members.splice(index, 1);
};

const submit = () => {
    form.put(route('projects.members.sync', props.project.id), {
        preserveScroll: true,
        onError: (errors) => notifyValidationErrors(errors),
    });
};

const accessDescription = (value) =>
    props.memberAccessOptions.find((a) => a.value === value)?.description ?? '';
</script>

<template>
    <div class="space-y-6">
        <div class="rounded-xl border border-sky-200/80 bg-sky-50/80 px-4 py-3 text-sm text-sky-900">
            <div class="flex gap-3">
                <InformationCircleIcon class="h-5 w-5 shrink-0 text-sky-600" />
                <div>
                    <p class="font-semibold">Akses otomatis tim divisi</p>
                    <p class="mt-1 text-sky-800/90">
                        User dengan divisi &amp; departemen yang sama dengan project dapat
                        <strong>melihat</strong> project dan task, tanpa bisa mengedit.
                        Hanya <strong>Project Manager</strong> yang mengelola project &amp; tab tim.
                        <strong>Admin</strong> tim: tambah/edit semua task.
                        <strong>Contributor</strong>: edit status &amp; deskripsi task yang ditugaskan ke mereka.
                    </p>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700"
                >
                    <UserGroupIcon class="h-6 w-6" />
                </div>
                <div>
                    <h2 class="font-bold text-slate-900">Project manager</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Mengelola project &amp; tim. Admin tim dapat menambah/mengedit task;
                        Contributor mengedit task yang ditugaskan ke mereka.
                    </p>
                    <div v-if="project.manager" class="mt-3 flex items-center gap-2">
                        <Avatar
                            :name="project.manager.name"
                            :initials="project.manager.initials"
                        />
                        <div>
                            <p class="font-semibold text-slate-800">{{ project.manager.name }}</p>
                            <Badge color="indigo">Project Manager</Badge>
                        </div>
                    </div>
                    <p v-else class="mt-2 text-sm italic text-slate-400">Belum ditentukan</p>
                </div>
            </div>
        </div>

        <div class="card overflow-visible">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="font-bold text-slate-900">Anggota tim project</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Tambahkan user ke tim project dan tentukan peran akses task mereka.
                    </p>
            </div>

            <form class="overflow-visible p-6" @submit.prevent="submit">
                <div v-if="form.members.length" class="space-y-4 overflow-visible">
                    <div
                        v-for="(row, index) in form.members"
                        :key="index"
                        class="overflow-visible rounded-xl border border-slate-200/80 bg-slate-50/50 p-4"
                    >
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_auto] xl:items-start">
                            <div class="min-w-0 overflow-visible">
                                <label class="form-label">User</label>
                                <SearchableSelect
                                    v-model="row.user_id"
                                    :options="
                                        userOptions.filter(
                                            (o) =>
                                                o.value === Number(row.user_id) ||
                                                !usedUserIds.includes(o.value)
                                        )
                                    "
                                    placeholder="Pilih user"
                                    class="mt-1"
                                />
                            </div>
                            <div class="min-w-0 overflow-visible">
                                <label class="form-label">Peran di tim</label>
                                <SearchableSelect
                                    v-model="row.access"
                                    :options="accessOptions"
                                    placeholder="Pilih peran"
                                    class="mt-1"
                                />
                                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">
                                    {{ accessDescription(row.access) }}
                                </p>
                            </div>
                            <div class="flex items-end justify-end md:col-span-2 xl:col-span-1 xl:pb-0.5">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                    title="Hapus baris"
                                    @click="removeMemberRow(index)"
                                >
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <p
                    v-else
                    class="rounded-xl border border-dashed border-slate-200 py-8 text-center text-sm text-slate-400"
                >
                    Belum ada anggota tambahan. Tambahkan user di bawah.
                </p>

                <div class="mt-4 flex flex-wrap gap-3">
                    <SecondaryButton type="button" @click="addMemberRow">
                        <PlusIcon class="h-4 w-4" />
                        Tambah anggota
                    </SecondaryButton>
                    <PrimaryButton :disabled="form.processing">
                        Simpan anggota tim
                    </PrimaryButton>
                </div>

                <p v-if="form.errors.members" class="mt-2 text-sm text-rose-600">
                    {{ form.errors.members }}
                </p>
            </form>
        </div>

        <div class="card p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">
                Peran anggota tim
            </h3>
            <ul class="mt-4 space-y-3">
                <li
                    v-for="opt in memberAccessOptions"
                    :key="opt.value"
                    class="flex flex-wrap gap-3 text-sm"
                >
                    <Badge :color="opt.color" class="shrink-0">{{ opt.label }}</Badge>
                    <span class="min-w-0 flex-1 text-slate-600">{{ opt.description }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
