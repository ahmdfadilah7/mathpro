<script setup>
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { ref } from 'vue';

const model = defineModel({ type: String, default: '' });

const props = defineProps({
    placeholder: { type: String, default: 'Tulis deskripsi project...' },
    minHeight: { type: String, default: '160px' },
});

const toolbar = [
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ header: [2, 3, false] }],
    ['link'],
    ['clean'],
];

</script>

<template>
    <div class="rich-editor overflow-hidden rounded-xl border border-slate-200 bg-white">
        <QuillEditor
            v-model:content="model"
            content-type="html"
            theme="snow"
            :toolbar="toolbar"
            :placeholder="placeholder"
            :style="{ minHeight }"
        />
    </div>
</template>

<style>
.rich-editor .ql-toolbar {
    border: none;
    border-bottom: 1px solid rgb(226 232 240);
    background: rgb(248 250 252);
    border-radius: 0.75rem 0.75rem 0 0;
    font-family: inherit;
}

.rich-editor .ql-container {
    border: none;
    font-family: inherit;
    font-size: 0.875rem;
}

.rich-editor .ql-editor {
    min-height: v-bind(minHeight);
    color: rgb(30 41 59);
}

.rich-editor .ql-editor.ql-blank::before {
    color: rgb(148 163 184);
    font-style: normal;
}
</style>
