<script setup>
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import multiMonthPlugin from '@fullcalendar/multimonth';
import idLocale from '@fullcalendar/core/locales/id';
import { computed } from 'vue';

const props = defineProps({
    year: { type: Number, required: true },
    events: { type: Array, default: () => [] },
});

const emit = defineEmits(['task-click', 'project-click']);

const calendarOptions = computed(() => ({
    plugins: [multiMonthPlugin, dayGridPlugin, interactionPlugin],
    initialView: 'multiMonthYear',
    initialDate: `${props.year}-01-01`,
    headerToolbar: false,
    height: 'auto',
    firstDay: 1,
    locale: idLocale,
    multiMonthMaxColumns: 4,
    multiMonthMinWidth: 220,
    fixedWeekCount: false,
    showNonCurrentDates: true,
    dayMaxEvents: 2,
    moreLinkClick: 'popover',
    events: props.events,
    eventClick(info) {
        info.jsEvent.preventDefault();
        const type = info.event.extendedProps?.type;

        if (type === 'task' && info.event.extendedProps?.task) {
            emit('task-click', info.event.extendedProps.task);
            return;
        }

        if (type === 'project' && info.event.extendedProps?.href) {
            emit('project-click', info.event.extendedProps.href);
        }
    },
    eventDidMount(info) {
        if (info.event.extendedProps?.type === 'task') {
            info.el.classList.add('cursor-pointer');
        }
    },
}));
</script>

<template>
    <div class="full-year-calendar p-4">
        <FullCalendar :key="year" :options="calendarOptions" />
    </div>
</template>

<style>
.full-year-calendar .fc {
    --fc-border-color: rgb(226 232 240);
    --fc-button-bg-color: rgb(20 184 166);
    --fc-button-border-color: rgb(20 184 166);
    --fc-button-hover-bg-color: rgb(13 148 136);
    --fc-button-hover-border-color: rgb(13 148 136);
    --fc-today-bg-color: rgb(240 253 250);
    font-family: inherit;
}

.full-year-calendar .fc-multimonth-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: rgb(15 23 42);
}

.full-year-calendar .fc-col-header-cell-cushion,
.full-year-calendar .fc-daygrid-day-number {
    font-size: 0.7rem;
    color: rgb(100 116 139);
}

.full-year-calendar .fc-daygrid-event {
    font-size: 0.65rem;
    font-weight: 600;
    border-radius: 0.25rem;
    padding: 1px 3px;
}

.full-year-calendar .fc-day-today .fc-daygrid-day-number {
    background: rgb(20 184 166);
    color: white;
    border-radius: 9999px;
    width: 1.35rem;
    height: 1.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 2px;
}
</style>
