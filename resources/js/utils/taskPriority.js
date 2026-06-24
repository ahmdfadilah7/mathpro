/**
 * Warna kartu task — selaras TaskPriority::color() + Badge.vue
 * low → slate, medium → brand, high → amber, urgent → rose
 */

const COLOR_TO_PRIORITY = {
    slate: 'low',
    brand: 'medium',
    amber: 'high',
    rose: 'urgent',
};

/** @type {Record<string, { card: string, drag: string }>} */
export const taskPriorityCardClasses = {
    low: {
        card: 'border border-slate-200/90 bg-slate-100/90 border-l-4 border-l-slate-500 hover:border-slate-300 hover:bg-slate-100',
        drag: 'hover:border-slate-400 hover:shadow-md',
    },
    medium: {
        card: 'border border-brand-200/90 bg-brand-100/80 border-l-4 border-l-brand-600 hover:border-brand-300 hover:bg-brand-100',
        drag: 'hover:border-brand-400 hover:shadow-md hover:shadow-brand-500/10',
    },
    high: {
        card: 'border border-amber-200/90 bg-amber-100/80 border-l-4 border-l-amber-500 hover:border-amber-300 hover:bg-amber-100',
        drag: 'hover:border-amber-400 hover:shadow-md hover:shadow-amber-500/10',
    },
    urgent: {
        card: 'border border-rose-200/90 bg-rose-100/80 border-l-4 border-l-rose-500 hover:border-rose-300 hover:bg-rose-100',
        drag: 'hover:border-rose-400 hover:shadow-md hover:shadow-rose-500/10',
    },
};

/** Teks & pemisah — kontras tinggi di atas background prioritas */
export const taskPriorityTextClasses = {
    low: {
        title: 'text-slate-900',
        body: 'text-slate-800',
        meta: 'text-slate-800',
        faint: 'text-slate-700',
        border: 'border-slate-300/80',
        icon: 'text-slate-700',
        action: 'text-slate-700 hover:bg-slate-200/80 hover:text-slate-900',
        delete: 'text-slate-700 hover:bg-rose-100 hover:text-rose-700',
    },
    medium: {
        title: 'text-brand-900',
        body: 'text-brand-800',
        meta: 'text-brand-800',
        faint: 'text-brand-800',
        border: 'border-brand-300/80',
        icon: 'text-brand-800',
        action: 'text-brand-800 hover:bg-brand-200/60 hover:text-brand-900',
        delete: 'text-brand-800 hover:bg-rose-100 hover:text-rose-700',
    },
    high: {
        title: 'text-amber-950',
        body: 'text-amber-900',
        meta: 'text-amber-900',
        faint: 'text-amber-800',
        border: 'border-amber-300/80',
        icon: 'text-amber-800',
        action: 'text-amber-800 hover:bg-amber-200/70 hover:text-amber-950',
        delete: 'text-amber-800 hover:bg-rose-100 hover:text-rose-700',
    },
    urgent: {
        title: 'text-rose-950',
        body: 'text-rose-900',
        meta: 'text-rose-900',
        faint: 'text-rose-800',
        border: 'border-rose-300/80',
        icon: 'text-rose-800',
        action: 'text-rose-800 hover:bg-rose-200/70 hover:text-rose-950',
        delete: 'text-rose-800 hover:bg-rose-100 hover:text-rose-700',
    },
};

function resolvePriorityKey(taskOrPriority) {
    if (taskOrPriority == null) {
        return 'low';
    }

    if (typeof taskOrPriority === 'string') {
        if (taskPriorityCardClasses[taskOrPriority]) {
            return taskOrPriority;
        }
        if (COLOR_TO_PRIORITY[taskOrPriority]) {
            return COLOR_TO_PRIORITY[taskOrPriority];
        }

        return 'low';
    }

    if (taskOrPriority.priority && taskPriorityCardClasses[taskOrPriority.priority]) {
        return taskOrPriority.priority;
    }

    if (taskOrPriority.priority_color && COLOR_TO_PRIORITY[taskOrPriority.priority_color]) {
        return COLOR_TO_PRIORITY[taskOrPriority.priority_color];
    }

    return 'low';
}

/**
 * @param {string|{ priority?: string, priority_color?: string }} taskOrPriority
 * @param {{ draggable?: boolean }} [options]
 */
export function getTaskPriorityCardClass(taskOrPriority, { draggable = false } = {}) {
    const key = resolvePriorityKey(taskOrPriority);
    const styles = taskPriorityCardClasses[key] ?? taskPriorityCardClasses.low;

    return draggable ? `${styles.card} ${styles.drag}` : styles.card;
}

/**
 * @param {string|{ priority?: string, priority_color?: string }} taskOrPriority
 */
export function getTaskPriorityTextClass(taskOrPriority) {
    const key = resolvePriorityKey(taskOrPriority);

    return taskPriorityTextClasses[key] ?? taskPriorityTextClasses.low;
}
