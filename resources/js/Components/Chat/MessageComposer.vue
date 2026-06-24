<script setup>
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { PaperClipIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    processing: { type: Boolean, default: false },
    placeholder: { type: String, default: 'Tulis pesan…' },
});

const emit = defineEmits(['submit']);

const body = ref('');
const fileInput = ref(null);
const selectedFiles = ref([]);

const onFiles = (event) => {
    const files = Array.from(event.target.files ?? []);
    selectedFiles.value = [...selectedFiles.value, ...files].slice(0, 5);
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const removeFile = (index) => {
    selectedFiles.value = selectedFiles.value.filter((_, i) => i !== index);
};

const submit = () => {
    const text = body.value.trim();
    if (!text && selectedFiles.value.length === 0) {
        return;
    }

    emit('submit', { body: text, files: selectedFiles.value });
    body.value = '';
    selectedFiles.value = [];
};

const onKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        submit();
    }
};
</script>

<template>
    <div class="border-t border-slate-100 bg-white p-4">
        <ul
            v-if="selectedFiles.length"
            class="mb-3 flex flex-wrap gap-2"
        >
            <li
                v-for="(file, index) in selectedFiles"
                :key="index"
                class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700"
            >
                <PaperClipIcon class="h-3.5 w-3.5 shrink-0" />
                <span class="max-w-[160px] truncate">{{ file.name }}</span>
                <button
                    type="button"
                    class="text-slate-400 hover:text-rose-600"
                    @click="removeFile(index)"
                >
                    ×
                </button>
            </li>
        </ul>

        <div class="flex items-end gap-2">
            <label class="shrink-0 cursor-pointer rounded-xl border border-slate-200 p-2.5 text-slate-500 transition hover:bg-slate-50 hover:text-brand-600">
                <PaperClipIcon class="h-5 w-5" />
                <input
                    ref="fileInput"
                    type="file"
                    class="hidden"
                    multiple
                    accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip"
                    @change="onFiles"
                />
            </label>
            <textarea
                v-model="body"
                rows="2"
                class="form-input min-h-[44px] flex-1 resize-none"
                :placeholder="placeholder"
                @keydown="onKeydown"
            />
            <PrimaryButton
                type="button"
                class="shrink-0 !px-4"
                :disabled="processing"
                @click="submit"
            >
                <PaperAirplaneIcon class="h-4 w-4" />
            </PrimaryButton>
        </div>
        <p class="mt-2 text-[10px] text-slate-400">
            Enter kirim · Shift+Enter baris baru · Maks. 5 file (10 MB)
        </p>
    </div>
</template>
