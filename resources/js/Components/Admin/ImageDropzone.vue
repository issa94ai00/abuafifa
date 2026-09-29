<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AdminIcon from './AdminIcon.vue';
import { compressImages } from '@/Composables/useImageCompression';

/**
 * The picker shared by every marketing upload (offer banner, gallery
 * cover, gallery images, media library).
 *
 * The five hand-rolled `<input type="file">` blocks it replaces were
 * click-only, accepted `image/*` — looser than the server, which takes
 * only JPEG/PNG/WebP/AVIF — and did no size check, so a 40MB HEIC
 * uploaded in full before the server rejected it. They also called
 * URL.createObjectURL() without ever revoking on unmount, so every
 * preview stayed resident until a full page load.
 */
const props = defineProps({
    /** v-model: File for single mode, File[] for multiple. */
    modelValue: { type: [Object, Array], default: null },
    multiple: { type: Boolean, default: false },
    /** Already-stored image shown when nothing new is staged. */
    existingUrl: { type: String, default: null },
    /** Server ceiling for this field, in MB. */
    maxSizeMb: { type: Number, default: 5 },
    /** Server ceiling on how many files may be staged at once. */
    maxFiles: { type: Number, default: 25 },
    label: { type: String, default: 'اسحب الصور هنا أو اضغط للاختيار' },
    disabled: { type: Boolean, default: false },
    /** Progress 0..100 while the parent form is uploading. */
    progress: { type: Number, default: null },
});

const emit = defineEmits(['update:modelValue', 'remove-existing']);

/**
 * Mirrors config/security.php `uploads.allowed_mimes`. Listing the types
 * explicitly rather than `image/*` means the OS file dialog greys out
 * what the server would reject anyway.
 */
const ACCEPT = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
const ACCEPT_ATTR = ACCEPT.join(',');

const input = ref(null);
const dragging = ref(false);
const rejected = ref([]);
const compressing = ref(false);
const compressed = ref({ done: 0, total: 0 });

/** @type {import('vue').Ref<{file: File, url: string}[]>} */
const staged = ref([]);

const maxBytes = computed(() => props.maxSizeMb * 1024 * 1024);

const showExisting = computed(() => !staged.value.length && !!props.existingUrl);

const isEmpty = computed(() => !staged.value.length && !props.existingUrl);

function formatBytes(bytes) {
    if (!bytes) return '';
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${Math.round(bytes / 1024)} KB`;
}

/** Free a blob URL; safe to call twice. */
function release(entry) {
    if (entry?.url) URL.revokeObjectURL(entry.url);
}

function releaseAll() {
    staged.value.forEach(release);
    staged.value = [];
}

/**
 * Type screening runs first and on its own, because an unsupported type
 * is the one problem compressing cannot fix.
 */
function acceptTypes(files) {
    const problems = [];
    const ok = [];

    for (const file of files) {
        if (!ACCEPT.includes(file.type)) {
            problems.push(`${file.name}: صيغة غير مدعومة (المسموح: JPG، PNG، WebP، AVIF)`);
            continue;
        }

        ok.push(file);
    }

    return { ok, problems };
}

/** Pair each file with what it weighed before compression, for the badge. */
function stage(file, originalSize) {
    return { file, originalSize, url: URL.createObjectURL(file) };
}

async function addFiles(fileList) {
    if (props.disabled) return;

    const incoming = Array.from(fileList ?? []);
    if (!incoming.length) return;

    const typed = acceptTypes(incoming);
    rejected.value = typed.problems;

    if (!typed.ok.length) return;

    // Trim to the remaining room before compressing, so work is not spent
    // on files that are about to be turned away for being over the count.
    let queue = typed.ok;
    const problems = [...typed.problems];

    if (props.multiple) {
        const room = props.maxFiles - staged.value.length;

        if (room <= 0) {
            rejected.value = [...problems, `الحد الأقصى ${props.maxFiles} صورة في المرة الواحدة.`];
            return;
        }

        if (queue.length > room) {
            problems.push(`تمت إضافة ${room} فقط — الحد الأقصى ${props.maxFiles} صورة.`);
            queue = queue.slice(0, room);
        }
    } else {
        queue = queue.slice(0, 1);
    }

    const originalSizes = queue.map((file) => file.size);

    compressing.value = true;
    compressed.value = { done: 0, total: queue.length };

    let processed;

    try {
        processed = await compressImages(queue, (done, total) => {
            compressed.value = { done, total };
        });
    } finally {
        compressing.value = false;
    }

    // A file still over the ceiling after compression is genuinely too big.
    const kept = [];
    const keptOriginals = [];

    processed.forEach((file, index) => {
        if (file.size > maxBytes.value) {
            problems.push(`${file.name}: الحجم ${formatBytes(file.size)} يتجاوز ${props.maxSizeMb}MB حتى بعد الضغط`);
            return;
        }

        kept.push(file);
        keptOriginals.push(originalSizes[index]);
    });

    rejected.value = problems;

    if (!kept.length) return;

    if (props.multiple) {
        staged.value.push(...kept.map((file, i) => stage(file, keptOriginals[i])));
        emit('update:modelValue', staged.value.map((entry) => entry.file));
        return;
    }

    releaseAll();
    staged.value = [stage(kept[0], keptOriginals[0])];
    emit('update:modelValue', kept[0]);
}

/** Total bytes saved across everything staged, for the summary line. */
const savedBytes = computed(() =>
    staged.value.reduce((sum, e) => sum + Math.max(0, (e.originalSize ?? e.file.size) - e.file.size), 0)
);

async function onPick(event) {
    // Copy before resetting the input: `files` is live, so clearing the
    // value would empty the very list we are about to read.
    const picked = Array.from(event.target.files ?? []);
    // Lets the same file be re-picked after being removed.
    event.target.value = '';
    await addFiles(picked);
}

async function onDrop(event) {
    dragging.value = false;
    await addFiles(event.dataTransfer?.files);
}

function onDragOver() {
    if (!props.disabled) dragging.value = true;
}

function removeAt(index) {
    release(staged.value[index]);
    staged.value.splice(index, 1);
    emit('update:modelValue', props.multiple ? staged.value.map((e) => e.file) : null);
}

/** Clears the stored image too, not just what is staged. */
function clearAll() {
    releaseAll();
    rejected.value = [];
    emit('update:modelValue', props.multiple ? [] : null);
    if (props.existingUrl) emit('remove-existing');
}

function openPicker() {
    if (!props.disabled) input.value?.click();
}

/**
 * A parent that resets its form (modal closed, save succeeded) sets the
 * bound value back to null/[]; the previews have to follow, or they
 * would outlive the files they describe.
 */
watch(
    () => props.modelValue,
    (value) => {
        const emptied = props.multiple ? !value || value.length === 0 : !value;

        if (emptied && staged.value.length) {
            releaseAll();
            rejected.value = [];
        }
    },
);

onBeforeUnmount(releaseAll);
</script>

<template>
    <div>
        <div
            class="relative rounded-2xl border-2 border-dashed transition"
            :class="[
                dragging
                    ? 'border-brand-500 bg-brand-50/60'
                    : 'border-surface-300 bg-surface-50 hover:border-brand-400 hover:bg-brand-50/30',
                disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
            ]"
            role="button"
            tabindex="0"
            :aria-label="label"
            :aria-disabled="disabled"
            @click="openPicker"
            @keydown.enter.prevent="openPicker"
            @keydown.space.prevent="openPicker"
            @dragenter.prevent="onDragOver"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <!-- Empty -->
            <div v-if="isEmpty" class="flex flex-col items-center justify-center gap-2 px-4 py-10 text-center">
                <AdminIcon name="image" size="xl" :class="dragging ? 'text-brand-500' : 'text-surface-300'" />
                <p class="text-sm font-semibold" :class="dragging ? 'text-brand-600' : 'text-surface-500'">
                    {{ dragging ? 'أفلت الصور هنا' : label }}
                </p>
                <p class="text-xs font-normal text-surface-400">
                    JPG · PNG · WebP · AVIF — حتى {{ maxSizeMb }}MB<span v-if="multiple"> للصورة، {{ maxFiles }} صورة كحد أقصى</span>
                </p>
            </div>

            <!-- Single: stored or staged -->
            <div v-else-if="!multiple" class="p-3">
                <div class="relative mx-auto aspect-video max-h-56 overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200">
                    <img
                        :src="staged.length ? staged[0].url : existingUrl"
                        alt=""
                        class="h-full w-full object-cover"
                    />
                    <span
                        v-if="staged.length"
                        class="absolute bottom-2 left-2 rounded-md bg-black/60 px-1.5 py-0.5 text-[10px] font-medium text-white"
                    >
                        {{ formatBytes(staged[0].file.size) }}
                    </span>
                    <span
                        v-else
                        class="absolute bottom-2 left-2 rounded-md bg-black/60 px-1.5 py-0.5 text-[10px] font-medium text-white"
                    >
                        الصورة الحالية
                    </span>
                </div>
                <p class="mt-2 text-center text-xs text-surface-400">اضغط أو اسحب صورة جديدة لاستبدالها</p>
            </div>

            <!-- Multiple: staged grid -->
            <div v-else class="p-3">
                <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-4">
                    <div
                        v-for="(entry, index) in staged"
                        :key="entry.url"
                        class="group relative aspect-square overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200"
                    >
                        <img :src="entry.url" :alt="entry.file.name" class="h-full w-full object-cover" />
                        <button
                            type="button"
                            class="absolute right-1.5 top-1.5 grid h-7 w-7 place-items-center rounded-lg bg-black/55 text-white opacity-0 transition hover:bg-red-600 focus:opacity-100 group-hover:opacity-100"
                            :aria-label="`إزالة ${entry.file.name}`"
                            @click.stop="removeAt(index)"
                        >
                            <AdminIcon name="close" size="xs" />
                        </button>
                        <span class="absolute bottom-1.5 left-1.5 rounded-md bg-black/55 px-1.5 py-0.5 text-[10px] text-white">
                            {{ formatBytes(entry.file.size) }}
                        </span>
                    </div>

                    <div class="grid aspect-square place-items-center rounded-xl border-2 border-dashed border-surface-300 text-surface-400 transition hover:border-brand-400 hover:text-brand-500">
                        <div class="text-center">
                            <AdminIcon name="plus" size="md" />
                            <p class="mt-1 text-[10px] font-semibold">إضافة</p>
                        </div>
                    </div>
                </div>
                <p class="mt-2 text-center text-xs text-surface-400">
                    {{ staged.length }} من {{ maxFiles }} — اسحب المزيد أو اضغط للإضافة
                </p>
            </div>

            <input
                ref="input"
                type="file"
                class="sr-only"
                :accept="ACCEPT_ATTR"
                :multiple="multiple"
                :disabled="disabled"
                @change="onPick"
            />

            <!--
                Compressing a batch of full-size photos takes a moment and
                happens before any upload starts; without this the picker
                just looks frozen.
            -->
            <div
                v-if="compressing"
                class="absolute inset-0 grid place-items-center rounded-2xl bg-white/80 backdrop-blur-[1px]"
                role="status"
                aria-live="polite"
            >
                <div class="text-center">
                    <AdminIcon name="image" size="lg" class="mx-auto animate-pulse text-brand-500" />
                    <p class="mt-1.5 text-xs font-semibold text-surface-600">
                        جارٍ ضغط الصور… {{ compressed.done }}/{{ compressed.total }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Upload progress: a large gallery batch is a long silent wait otherwise -->
        <div v-if="progress !== null" class="mt-2">
            <div class="h-1.5 overflow-hidden rounded-full bg-surface-200">
                <div
                    class="h-full rounded-full bg-brand-500 transition-[width] duration-200"
                    :style="{ width: `${progress}%` }"
                ></div>
            </div>
            <p class="mt-1 text-center text-xs font-medium text-surface-500">جارٍ الرفع… {{ progress }}%</p>
        </div>

        <!-- What compression bought, so the smaller badge size makes sense -->
        <p v-if="savedBytes > 0" class="mt-2 flex items-center gap-1.5 text-xs font-medium text-emerald-600">
            <AdminIcon name="check" size="xs" class="shrink-0" />
            <span>تم ضغط الصور قبل الرفع — توفير {{ formatBytes(savedBytes) }}</span>
        </p>

        <!-- Client-side rejections -->
        <ul v-if="rejected.length" class="mt-2 space-y-1">
            <li
                v-for="(problem, i) in rejected"
                :key="i"
                class="flex items-start gap-1.5 text-xs font-medium text-red-600"
            >
                <AdminIcon name="alert" size="xs" class="mt-0.5 shrink-0" />
                <span>{{ problem }}</span>
            </li>
        </ul>

        <button
            v-if="!isEmpty && !disabled"
            type="button"
            class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-red-600 transition hover:text-red-700"
            @click="clearAll"
        >
            <AdminIcon name="trash" size="xs" />
            {{ multiple ? 'إزالة الكل' : 'إزالة الصورة' }}
        </button>
    </div>
</template>
