<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import InputError from '@/Components/InputError.vue';
import AdminToast from '@/Components/Admin/AdminToast.vue';
import { compressImage } from '@/Composables/useImageCompression';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    group: String,
    settings: Object,
    social_links: { type: Array, default: () => [] },
    groups: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props.auth?.user?.permissions ?? []);
const can = (p) => permissions.value.includes(p);

// ------------------------------------------------------------ configuration
const groups = {
    site: {
        label: 'معلومات الموقع',
        icon: 'cog',
        hint: 'الاسم، الشعار والعملة الأساسية للموقع',
        fields: [
            { key: 'name', label: 'اسم الموقع', type: 'text', col: 2, placeholder: 'مثال: أبو عفيفا' },
            { key: 'title', label: 'عنوان الموقع', type: 'text', col: 2, placeholder: 'عنوان يظهر في برامج المطالعة والمحركات' },
            { key: 'description', label: 'وصف الموقع', type: 'textarea', col: 2, placeholder: 'وصف قصير عن نشاط الموقع' },
            {
                key: 'currency',
                label: 'العملة',
                type: 'select',
                col: 1,
                options: [
                    { value: 'JOD', label: 'دينار أردني (JOD)' },
                    { value: 'SYP', label: 'ليرة سورية (SYP)' },
                    { value: 'USD', label: 'دولار أمريكي (USD)' },
                    { value: 'EUR', label: 'يورو (EUR)' },
                ],
            },
            { key: 'email', label: 'البريد الإلكتروني العام', type: 'email', col: 1, placeholder: 'info@example.com' },
            { key: 'logo', label: 'الشعار', type: 'image', col: 1, hint: 'بحد أقصى 4MB', accept: 'image/jpeg,image/png,image/webp,image/avif' },
            { key: 'favicon', label: 'أيقونة المتصفح (Favicon)', type: 'image', col: 1, hint: 'بحد أقصى 1MB', accept: 'image/jpeg,image/png,image/webp,image/avif' },
        ],
    },
    contact: {
        label: 'بيانات التواصل',
        icon: 'mail',
        hint: 'ما يظهر للزبون في صفحة «اتصل بنا» وأسفل كل صفحة',
        sections: [
            { id: 'channels', label: 'طرق التواصل', icon: 'phone', hint: 'كل رقم يتحوّل إلى رابط اتصال مباشر للزبون.' },
            { id: 'address', label: 'العنوان', icon: 'home', hint: 'يظهر نصاً كما تكتبه تماماً.' },
            { id: 'map', label: 'الموقع على الخريطة', icon: 'map-pin', hint: 'موضع الدبوس في خريطة صفحة التواصل.' },
            { id: 'hours', label: 'ساعات العمل', icon: 'clock' },
        ],
        fields: [
            { key: 'phone', section: 'channels', label: 'رقم الهاتف', type: 'tel', col: 1, placeholder: '+962 3 201 5555', dir: 'ltr', preview: 'tel' },
            { key: 'whatsapp', section: 'channels', label: 'رقم واتساب', type: 'tel', col: 1, placeholder: '+962 79 012 3456', dir: 'ltr', preview: 'whatsapp' },
            { key: 'email', section: 'channels', label: 'بريد التواصل', type: 'email', col: 2, placeholder: 'contact@example.com', dir: 'ltr', preview: 'email' },
            { key: 'address_ar', section: 'address', label: 'العنوان بالعربية', type: 'textarea', col: 1, placeholder: 'شارع ...، العقبة، الأردن' },
            { key: 'address_en', section: 'address', label: 'العنوان بالإنجليزية', type: 'textarea', col: 1, placeholder: 'Aqaba, Jordan', dir: 'ltr' },
            { key: 'map_lat', section: 'map', label: 'خط العرض (Latitude)', type: 'number', col: 1, placeholder: '29.5267', dir: 'ltr', step: 'any' },
            { key: 'map_lng', section: 'map', label: 'خط الطول (Longitude)', type: 'number', col: 1, placeholder: '35.0078', dir: 'ltr', step: 'any' },
            { key: 'working_hours', section: 'hours', label: 'ساعات العمل', type: 'text', col: 2, placeholder: 'السبت - الخميس: 10 صباحاً - 9 مساءً · الجمعة: مغلق' },
        ],
    },
    seo: {
        label: 'تحسين محركات البحث',
        icon: 'sparkles',
        hint: 'الإعدادات الافتراضية لظهور الموقع في البحث ووسائل التواصل',
        fields: [
            { key: 'default_title', label: 'العنوان الافتراضي للصفحات', type: 'text', col: 2, placeholder: 'أثاث منزلي في العقبة | أبو عفيفة' },
            { key: 'default_description', label: 'الوصف الافتراضي', type: 'textarea', col: 2, placeholder: 'وصف يظهر في نتائج البحث' },
            { key: 'default_keywords', label: 'الكلمات المفتاحية', type: 'text', col: 2, placeholder: 'أثاث، صالونات، غرف نوم، …' },
            { key: 'og_image', label: 'صورة المشاركة (Open Graph)', type: 'image', col: 1, hint: 'تظهر عند مشاركة الموقع بحد أقصى 4MB', accept: 'image/jpeg,image/png,image/webp,image/avif' },
        ],
    },
    homepage: {
        label: 'الواجهة الرئيسية',
        icon: 'home',
        hint: 'محتوى صفحة الواجهة والخيارات المعروضة للزوار',
        fields: [
            { key: 'hero_title_ar', label: 'عنوان الواجهة بالعربية', type: 'text', col: 2, placeholder: 'مثال: أثاث يعكس ذوقك' },
            { key: 'hero_subtitle_ar', label: 'وصف الواجهة بالعربية', type: 'textarea', col: 2, placeholder: 'جملة توضيحية تظهر أسفل العنوان' },
            { key: 'hero_image', label: 'صورة الواجهة', type: 'image', col: 1, hint: 'بحد أقصى 6MB', accept: 'image/jpeg,image/png,image/webp,image/avif' },
            { key: 'show_featured', label: 'عرض المنتجات المميزة', type: 'switch', col: 1 },
            { key: 'show_new_arrivals', label: 'عرض الوافدات الجديدة', type: 'switch', col: 1 },
            { key: 'show_offers', label: 'عرض العروض الخاصة', type: 'switch', col: 1 },
            { key: 'about_intro_ar', label: 'مقدمة «من نحن» بالعربية', type: 'textarea', col: 2, placeholder: 'نبذة تعريفية عن المتجر' },
            { key: 'about_intro_en', label: 'مقدمة «من نحن» بالإنجليزية', type: 'textarea', col: 2, placeholder: 'About us intro', dir: 'ltr' },
        ],
    },
};

const tabs = [
    ...props.groups.map((g) => ({ id: g, ...groups[g] })),
    { id: 'social', label: 'الروابط الاجتماعية', icon: 'external', hint: 'روابط الموقع في مواقع التواصل' },
];

const isSocial = ref(false);

const currentGroup = computed(() => groups[props.group] ?? groups.site);
const currentFields = computed(() => currentGroup.value.fields);

/**
 * Fields grouped under their section heading. A group that declares no
 * sections renders as one unlabelled block, exactly as before.
 */
const fieldSections = computed(() => {
    const group = currentGroup.value;

    if (!group.sections?.length) {
        return [{ id: '_all', label: null, fields: group.fields }];
    }

    return group.sections
        .map((section) => ({ ...section, fields: group.fields.filter((f) => f.section === section.id) }))
        .filter((section) => section.fields.length);
});

// ------------------------------------------------------------- form state
const allKeys = (() => {
    const seen = [];
    Object.values(groups).forEach((g) =>
        g.fields.forEach((f) => {
            if (!seen.includes(f.key)) seen.push(f.key);
        }),
    );
    return seen;
})();

const initial = Object.fromEntries(allKeys.map((k) => [k, '']));

const form = useForm(initial);

const imageFiles = reactive({});
const imagePreview = reactive({});
const imageDirty = reactive({});

function coerce(v) {
    if (v === true || v === 'true' || v === '1') return true;
    if (v === false || v === 'false' || v === '0' || v === '') return false;
    return v;
}

function syncDefaults() {
    form.clearErrors();

    const vals = {};
    Object.values(groups).forEach((g) =>
        g.fields.forEach((f) => {
            vals[f.key] = props.settings?.[f.key] !== undefined ? coerce(props.settings[f.key]) : '';
        }),
    );
    form.defaults(vals);
    form.reset();
    Object.keys(imageFiles).forEach((k) => imageFiles[k] && URL.revokeObjectURL(imagePreview[k]));
    Object.keys(imageDirty).forEach((k) => (imageDirty[k] = false));
}

const pendingTab = ref(null);

/** Unsaved edits in the current group, including a newly picked image. */
const isDirty = computed(
    () => form.isDirty || currentFields.value.some((f) => f.type === 'image' && imageDirty[f.key]),
);

function selectTab(tab) {
    if (isTabActive(tab)) return;

    // Moving away reloads the group's values, which would drop edits.
    if (!isSocial.value && isDirty.value) {
        pendingTab.value = tab;
        return;
    }

    goToTab(tab);
}

function goToTab(tab) {
    pendingTab.value = null;

    if (tab.id === 'social') {
        isSocial.value = true;
        syncSocial();
        return;
    }

    isSocial.value = false;

    if (tab.id !== props.group) {
        router.get(route('admin.settings.index'), { group: tab.id }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    } else {
        syncDefaults();
    }
}

function discardAndSwitch() {
    syncDefaults();
    if (pendingTab.value) goToTab(pendingTab.value);
}

function isTabActive(tab) {
    if (tab.id === 'social') return isSocial.value;
    return !isSocial.value && props.group === tab.id;
}

watch(
    () => props.group,
    () => {
        if (props.group && props.group !== 'social' && !isSocial.value) {
            syncDefaults();
        }
    },
);

onMounted(() => {
    syncDefaults();
    syncSocial();
});

async function onImageChange(fieldKey, event) {
    const picked = event.target.files?.[0];
    event.target.value = '';
    if (!picked) return;

    const file = await compressImage(picked);

    if (imagePreview[fieldKey]) URL.revokeObjectURL(imagePreview[fieldKey]);
    imageFiles[fieldKey] = file;
    imagePreview[fieldKey] = URL.createObjectURL(file);
    imageDirty[fieldKey] = true;
    delete form[fieldKey];
}

function imageUrl(fieldKey) {
    if (imageDirty[fieldKey] && imagePreview[fieldKey]) return imagePreview[fieldKey];

    const value = props.settings?.[fieldKey];
    return value && typeof value === 'string' ? value : null;
}

function submitGroup() {
    form.transform((data) => {
        const payload = {};
        currentFields.value.forEach((f) => {
            if (f.type === 'image') {
                if (imageFiles[f.key]) payload[f.key] = imageFiles[f.key];
            } else if (f.type === 'switch') {
                payload[f.key] = data[f.key] ? '1' : '0';
            } else {
                payload[f.key] = data[f.key];
            }
        });
        return payload;
    });

    form.put(route('admin.settings.update', props.group), {
        preserveScroll: true,
        onSuccess: () => {
            Object.keys(imageDirty).forEach((k) => (imageDirty[k] = false));
        },
        onError: () => {},
    });
}

// ---------------------------------------------------------- live previews
/** Digits a wa.me/tel: link needs — no separators, no 00 or 0 prefix. */
function dialDigits(value) {
    let digits = String(value ?? '').replace(/\D/g, '');
    if (digits.startsWith('00')) digits = digits.slice(2);
    return digits;
}

/** A leading + or 00 is an explicit country code; a bare 0 is a local prefix. */
function hasCountryCode(value) {
    const raw = String(value ?? '').trim();
    if (!raw) return false;
    if (raw.startsWith('+')) return true;
    const digits = raw.replace(/\D/g, '');
    return digits.startsWith('00') || !digits.startsWith('0');
}

const telPreview = computed(() => {
    const digits = dialDigits(form.phone);
    return digits ? `tel:+${digits}` : null;
});

const whatsappPreview = computed(() => {
    const digits = dialDigits(form.whatsapp);
    return digits ? `https://wa.me/${digits}` : null;
});

/** Warn before saving: a local number produces a wa.me link that resolves to nothing. */
const whatsappNeedsCountryCode = computed(
    () => !!String(form.whatsapp ?? '').trim() && !hasCountryCode(form.whatsapp),
);

const emailPreview = computed(() => {
    const value = String(form.email ?? '').trim();
    return value ? `mailto:${value}` : null;
});

// -------------------------------------------------------------------- map
const mapLat = computed(() => Number(form.map_lat));
const mapLng = computed(() => Number(form.map_lng));

const hasCoordinates = computed(
    () => String(form.map_lat ?? '') !== '' && String(form.map_lng ?? '') !== ''
        && Number.isFinite(mapLat.value) && Number.isFinite(mapLng.value)
        && Math.abs(mapLat.value) <= 90 && Math.abs(mapLng.value) <= 180,
);

/** Same embed the storefront contact page uses, so this preview is the real thing. */
const mapEmbedUrl = computed(() => {
    if (!hasCoordinates.value) return null;
    const lat = mapLat.value;
    const lng = mapLng.value;
    const bbox = [lng - 0.008, lat - 0.005, lng + 0.008, lat + 0.005].join('%2C');
    return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat}%2C${lng}`;
});

const mapLinkInput = ref('');
const mapLinkError = ref('');

/**
 * Pull a coordinate pair out of a pasted map URL so nobody has to read
 * latitude and longitude out of a link by hand.
 */
function parseCoordinates(text) {
    const value = String(text ?? '').trim();
    if (!value) return null;

    const patterns = [
        /@(-?\d{1,3}\.\d+),(-?\d{1,3}\.\d+)/,          // google maps /@lat,lng,15z
        /!3d(-?\d{1,3}\.\d+)!4d(-?\d{1,3}\.\d+)/,       // google maps place data
        /[?&](?:q|ll|mlat|center)=(-?\d{1,3}\.\d+)[,%2C]+(-?\d{1,3}\.\d+)/i,
        /#map=\d+\/(-?\d{1,3}\.\d+)\/(-?\d{1,3}\.\d+)/, // openstreetmap
        /^\s*(-?\d{1,3}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)\s*$/, // bare "lat, lng"
    ];

    for (const pattern of patterns) {
        const match = value.match(pattern);
        if (!match) continue;

        const lat = Number(match[1]);
        const lng = Number(match[2]);

        if (Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180) {
            return { lat, lng };
        }
    }

    return null;
}

function applyMapLink() {
    const parsed = parseCoordinates(mapLinkInput.value);

    if (!parsed) {
        mapLinkError.value = 'لم يتم العثور على إحداثيات في هذا الرابط. جرّب نسخ الرابط من شريط العنوان في خرائط Google.';
        return;
    }

    form.map_lat = String(parsed.lat);
    form.map_lng = String(parsed.lng);
    mapLinkInput.value = '';
    mapLinkError.value = '';
}

function clearCoordinates() {
    form.map_lat = '';
    form.map_lng = '';
}

// ------------------------------------------------------------- social links
const socialForm = useForm({ links: [] });

function syncSocial() {
    socialForm.clearErrors();
    socialForm.links = (props.social_links ?? []).map((l) => ({
        id: l.id ?? null,
        platform: l.platform ?? '',
        url: l.url ?? '',
        icon: l.icon ?? '',
        label_ar: l.label_ar ?? '',
        label_en: l.label_en ?? '',
        is_active: coerce(l.is_active) === true,
    }));
}

function addSocial() {
    socialForm.links.push({
        id: null,
        platform: '',
        url: '',
        icon: '',
        label_ar: '',
        label_en: '',
        is_active: true,
    });
}

function removeSocial(index) {
    socialForm.links.splice(index, 1);
}

function submitSocial() {
    socialForm.transform((data) => ({
        labels: undefined,
        links: data.links.map((l, i) => ({
            id: l.id,
            platform: l.platform.trim(),
            url: l.url.trim(),
            icon: l.icon.trim() || null,
            label_ar: l.label_ar.trim() || null,
            label_en: l.label_en.trim() || null,
            sort_order: i,
            is_active: l.is_active ? 1 : 0,
        })),
    }));
    socialForm.put(route('admin.settings.social'), {
        preserveScroll: true,
        onError: () => {},
    });
}

const linkError = (index, key) => socialForm.errors[`links.${index}.${key}`];

const canManage = computed(() => can('setting.manage'));
</script>

<template>
    <Head title="الإعدادات" />

    <AdminLayout>
        <!-- Header -->
        <div>
            <h2 class="text-base font-bold text-surface-900 sm:text-lg">الإعدادات</h2>
            <p class="mt-0.5 text-sm text-surface-400">إدارة إعدادات الموقع وقوائم التواصل</p>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Sidebar tabs -->
            <aside class="lg:col-span-3 xl:col-span-2">
                <nav class="card-admin animate-fade-in-up flex gap-1 overflow-x-auto p-2 lg:flex-col lg:overflow-visible">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="flex shrink-0 items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm font-semibold transition"
                        :class="
                            isTabActive(tab)
                                ? 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200'
                                : 'text-surface-500 hover:bg-surface-100 hover:text-surface-900'
                        "
                        @click="selectTab(tab)"
                    >
                        <AdminIcon :name="tab.icon" size="sm" />
                        <span class="whitespace-nowrap">{{ tab.label }}</span>
                    </button>
                </nav>
            </aside>

            <!-- Content -->
            <main class="lg:col-span-9 xl:col-span-10">
                <!-- Regular settings group -->
                <section v-if="!isSocial" class="card-admin animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.05s' }">
                    <div class="flex items-center justify-between gap-4 border-b border-surface-100 px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                                <AdminIcon :name="currentGroup.icon" size="sm" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-surface-900">{{ currentGroup.label }}</h3>
                                <p class="text-xs text-surface-400">{{ currentGroup.hint }}</p>
                            </div>
                        </div>
                        <span v-if="!canManage" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-surface-100 px-2.5 py-1 text-[11px] font-semibold text-surface-500">
                            <AdminIcon name="lock" size="xs" />
                            قراءة فقط
                        </span>
                    </div>

                    <form class="p-5 sm:p-6" @submit.prevent="submitGroup">
                        <div
                            v-for="(section, si) in fieldSections"
                            :key="section.id"
                            :class="si > 0 ? 'mt-8' : ''"
                        >
                            <div v-if="section.label" class="mb-4 flex items-center gap-2.5 border-b border-surface-100 pb-3">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-surface-100 text-surface-500">
                                    <AdminIcon :name="section.icon" size="sm" />
                                </span>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-bold text-surface-900">{{ section.label }}</h4>
                                    <p v-if="section.hint" class="text-xs text-surface-400">{{ section.hint }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-x-5 gap-y-5 sm:grid-cols-2">
                                <template v-for="field in section.fields" :key="field.key">
                            <!-- Image -->
                            <div v-if="field.type === 'image'" :class="field.col === 1 ? 'sm:col-span-1' : 'sm:col-span-2'">
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">{{ field.label }}</label>
                                <div class="flex items-start gap-4">
                                    <div v-if="imageUrl(field.key)" class="relative h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-surface-100 ring-1 ring-surface-200">
                                        <img :src="imageUrl(field.key)" :alt="field.label" class="h-full w-full object-cover" />
                                    </div>
                                    <div class="flex-1">
                                        <label
                                            :for="`setting-${field.key}`"
                                            class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-dashed border-surface-300 bg-surface-50 px-4 py-2 text-sm font-semibold text-surface-600 transition hover:border-brand-400 hover:text-brand-600"
                                        >
                                            <AdminIcon name="image" size="sm" />
                                            {{ imageDirty[field.key] ? 'استبدال التحديد' : (imageUrl(field.key) ? 'استبدال الصورة' : 'اختيار صورة') }}
                                        </label>
                                        <p class="mt-1.5 text-xs text-surface-400">{{ field.hint }}</p>
                                    </div>
                                </div>
                                <input :id="`setting-${field.key}`" type="file" :accept="field.accept" class="sr-only" :disabled="!canManage" @change="onImageChange(field.key, $event)" />
                                <InputError :message="form.errors[field.key]" class="mt-1.5" />
                            </div>

                            <!-- Switch -->
                            <div v-else-if="field.type === 'switch'" :class="field.col === 1 ? 'sm:col-span-1' : 'sm:col-span-2'">
                                <div class="flex items-center justify-between rounded-xl bg-surface-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-surface-700">{{ field.label }}</p>
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="!!form[field.key]"
                                        class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200 disabled:opacity-50"
                                        :class="form[field.key] ? 'bg-emerald-500' : 'bg-surface-300'"
                                        :disabled="!canManage"
                                        @click="form[field.key] = !form[field.key]"
                                    >
                                        <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all duration-200" :class="form[field.key] ? 'start-6' : 'start-0.5'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- Select -->
                            <div v-else-if="field.type === 'select'" :class="field.col === 1 ? 'sm:col-span-1' : 'sm:col-span-2'">
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">{{ field.label }}</label>
                                <select v-model="form[field.key]" class="input-admin cursor-pointer" :disabled="!canManage">
                                    <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                                <InputError :message="form.errors[field.key]" class="mt-1.5" />
                            </div>

                            <!-- Textarea -->
                            <div v-else-if="field.type === 'textarea'" :class="field.col === 1 ? 'sm:col-span-1' : 'sm:col-span-2'">
                                <label class="mb-1.5 block text-sm font-semibold text-surface-700">{{ field.label }}</label>
                                <textarea
                                    v-model="form[field.key]"
                                    rows="3"
                                    :class="['input-admin resize-none', field.dir === 'ltr' ? 'text-start' : '']"
                                    :dir="field.dir"
                                    :placeholder="field.placeholder"
                                    :disabled="!canManage"
                                ></textarea>
                                <InputError :message="form.errors[field.key]" class="mt-1.5" />
                            </div>

                            <!-- Default input -->
                            <div v-else :class="field.col === 1 ? 'sm:col-span-1' : 'sm:col-span-2'">
                                <label :for="`setting-${field.key}`" class="mb-1.5 block text-sm font-semibold text-surface-700">{{ field.label }}</label>
                                <input
                                    :id="`setting-${field.key}`"
                                    v-model="form[field.key]"
                                    :type="field.type"
                                    :dir="field.dir"
                                    :step="field.step"
                                    class="input-admin"
                                    :placeholder="field.placeholder"
                                    :disabled="!canManage"
                                />
                                <InputError :message="form.errors[field.key]" class="mt-1.5" />

                                <!-- What the visitor actually gets, resolved live -->
                                <p v-if="field.preview === 'tel' && telPreview" class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs text-surface-400">
                                    <AdminIcon name="phone" size="xs" />
                                    <span>يتصل بـ</span>
                                    <a :href="telPreview" class="font-semibold text-brand-600 hover:underline" dir="ltr">{{ telPreview }}</a>
                                </p>
                                <template v-else-if="field.preview === 'whatsapp'">
                                    <p v-if="whatsappNeedsCountryCode" class="mt-1.5 flex items-start gap-1.5 text-xs font-semibold text-amber-600">
                                        <AdminIcon name="alert" size="xs" class="mt-0.5 shrink-0" />
                                        <span>هذا رقم محلي. أضف رمز الدولة (مثل <span dir="ltr">+962</span>) وإلا لن يفتح رابط واتساب.</span>
                                    </p>
                                    <p v-else-if="whatsappPreview" class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs text-surface-400">
                                        <AdminIcon name="chat" size="xs" />
                                        <span>يفتح</span>
                                        <a :href="whatsappPreview" target="_blank" rel="noopener" class="font-semibold text-emerald-600 hover:underline" dir="ltr">{{ whatsappPreview }}</a>
                                    </p>
                                </template>
                                <p v-else-if="field.preview === 'email' && emailPreview" class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs text-surface-400">
                                    <AdminIcon name="mail" size="xs" />
                                    <a :href="emailPreview" class="font-semibold text-brand-600 hover:underline" dir="ltr">{{ emailPreview }}</a>
                                </p>
                            </div>
                                </template>

                                <!-- Coordinate helpers: paste a link, see the pin -->
                                <div v-if="section.id === 'map'" class="sm:col-span-2">
                                    <div class="rounded-xl border border-surface-200 bg-surface-50/60 p-4">
                                        <label for="setting-map-link" class="block text-xs font-semibold text-surface-600">
                                            أو الصق رابط الموقع وسنستخرج الإحداثيات
                                        </label>
                                        <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                            <input
                                                id="setting-map-link"
                                                v-model="mapLinkInput"
                                                type="text"
                                                dir="ltr"
                                                class="input-admin flex-1"
                                                placeholder="https://www.google.com/maps/@29.5267,35.0078,17z"
                                                :disabled="!canManage"
                                                @keydown.enter.prevent="applyMapLink"
                                            />
                                            <button
                                                type="button"
                                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2.5 text-sm font-semibold text-surface-700 transition hover:bg-surface-50 disabled:opacity-50"
                                                :disabled="!canManage || !mapLinkInput"
                                                @click="applyMapLink"
                                            >
                                                <AdminIcon name="map-pin" size="sm" />
                                                استخراج
                                            </button>
                                        </div>
                                        <p v-if="mapLinkError" class="mt-2 flex items-start gap-1.5 text-xs font-semibold text-red-600">
                                            <AdminIcon name="alert" size="xs" class="mt-0.5 shrink-0" />
                                            {{ mapLinkError }}
                                        </p>
                                        <p v-else class="mt-2 text-xs text-surface-400">
                                            يقبل روابط خرائط Google وOpenStreetMap، أو زوج إحداثيات مثل <span dir="ltr">29.5267, 35.0078</span>.
                                        </p>
                                    </div>

                                    <div class="mt-4">
                                        <div class="mb-2 flex items-center justify-between gap-3">
                                            <p class="text-xs font-semibold text-surface-600">معاينة موضع الدبوس</p>
                                            <button
                                                v-if="canManage && (form.map_lat !== '' || form.map_lng !== '')"
                                                type="button"
                                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                                                @click="clearCoordinates"
                                            >
                                                <AdminIcon name="close" size="xs" />
                                                مسح الإحداثيات
                                            </button>
                                        </div>
                                        <div v-if="mapEmbedUrl" class="overflow-hidden rounded-xl ring-1 ring-surface-200">
                                            <iframe
                                                :src="mapEmbedUrl"
                                                class="h-56 w-full border-0"
                                                loading="lazy"
                                                title="معاينة موقع المتجر على الخريطة"
                                                referrerpolicy="no-referrer-when-downgrade"
                                            ></iframe>
                                        </div>
                                        <div v-else class="grid h-56 place-items-center rounded-xl border border-dashed border-surface-300 bg-surface-50">
                                            <span class="flex flex-col items-center gap-1.5 px-6 text-center text-xs text-surface-400">
                                                <AdminIcon name="map-pin" size="lg" />
                                                أدخل خط العرض وخط الطول معاً لعرض المعاينة
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="canManage" class="mt-8 flex flex-col gap-3 border-t border-surface-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <p
                                class="flex items-center gap-1.5 text-xs font-semibold"
                                :class="isDirty ? 'text-amber-600' : 'text-surface-400'"
                            >
                                <AdminIcon :name="isDirty ? 'alert' : 'check'" size="xs" />
                                {{ isDirty ? 'لديك تغييرات غير محفوظة' : 'كل التغييرات محفوظة' }}
                            </p>

                            <div class="flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700 disabled:opacity-40"
                                    :disabled="form.processing || !isDirty"
                                    @click="syncDefaults"
                                >
                                    تراجع
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                                    :disabled="form.processing || !isDirty"
                                >
                                    <AdminIcon :name="form.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': form.processing }" />
                                    {{ form.processing ? 'جارٍ الحفظ...' : 'حفظ الإعدادات' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                <!-- Social links -->
                <section v-else class="card-admin animate-fade-in-up overflow-hidden" :style="{ animationDelay: '0.05s' }">
                    <div class="flex items-center justify-between gap-4 border-b border-surface-100 px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                                <AdminIcon name="external" size="sm" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-surface-900">الروابط الاجتماعية</h3>
                                <p class="text-xs text-surface-400">الروابط التي تظهر في تذييل الموقع</p>
                            </div>
                        </div>
                        <button
                            v-if="canManage"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-surface-100 px-3 py-1.5 text-xs font-semibold text-surface-600 transition hover:bg-surface-200"
                            @click="addSocial"
                        >
                            <AdminIcon name="plus" size="xs" />
                            إضافة رابط
                        </button>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div v-if="socialForm.links.length" class="space-y-4">
                            <div
                                v-for="(link, index) in socialForm.links"
                                :key="index"
                                class="rounded-2xl border border-surface-200 bg-surface-50/50 p-4"
                            >
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="min-w-[140px] flex-1">
                                        <label class="mb-1 block text-xs font-semibold text-surface-600">المنصة</label>
                                        <input
                                            v-model="link.platform"
                                            type="text"
                                            dir="ltr"
                                            class="input-admin"
                                            placeholder="facebook"
                                            :disabled="!canManage"
                                        />
                                        <InputError :message="linkError(index, 'platform')" class="mt-1" />
                                    </div>
                                    <div class="min-w-[220px] flex-[2]">
                                        <label class="mb-1 block text-xs font-semibold text-surface-600">الرابط</label>
                                        <input
                                            v-model="link.url"
                                            type="url"
                                            dir="ltr"
                                            class="input-admin"
                                            placeholder="https://facebook.com/abuafifa"
                                            :disabled="!canManage"
                                        />
                                        <InputError :message="linkError(index, 'url')" class="mt-1" />
                                    </div>
                                    <div class="min-w-[120px] flex-1">
                                        <label class="mb-1 block text-xs font-semibold text-surface-600">اسم الأيقونة</label>
                                        <input
                                            v-model="link.icon"
                                            type="text"
                                            dir="ltr"
                                            class="input-admin"
                                            placeholder="facebook"
                                            :disabled="!canManage"
                                        />
                                    </div>
                                    <div class="flex items-center gap-3 pb-1">
                                        <button
                                            type="button"
                                            role="switch"
                                            :aria-checked="!!link.is_active"
                                            class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200 disabled:opacity-50"
                                            :class="link.is_active ? 'bg-emerald-500' : 'bg-surface-300'"
                                            :disabled="!canManage"
                                            title="تفعيل الكل"
                                            @click="link.is_active = !link.is_active"
                                        >
                                            <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all duration-200" :class="link.is_active ? 'start-6' : 'start-0.5'"></span>
                                        </button>
                                        <button
                                            v-if="canManage"
                                            type="button"
                                            class="grid h-9 w-9 place-items-center rounded-lg text-surface-400 transition hover:bg-red-50 hover:text-red-600"
                                            :aria-label="`حذف الرابط ${index + 1}`"
                                            @click="removeSocial(index)"
                                        >
                                            <AdminIcon name="trash" size="sm" />
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <input
                                        v-model="link.label_ar"
                                        type="text"
                                        class="input-admin"
                                        placeholder="اسم المنصة بالعربية (اختياري)"
                                        :disabled="!canManage"
                                    />
                                    <input
                                        v-model="link.label_en"
                                        type="text"
                                        dir="ltr"
                                        class="input-admin"
                                        placeholder="Platform label in English (optional)"
                                        :disabled="!canManage"
                                    />
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center px-6 py-14 text-center">
                            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-surface-100 text-surface-400">
                                <AdminIcon name="external" size="lg" />
                            </span>
                            <p class="mt-3 text-sm text-surface-400">لا توجد روابط اجتماعية بعد.</p>
                            <button
                                v-if="canManage"
                                type="button"
                                class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-gold transition hover:bg-brand-700"
                                @click="addSocial"
                            >
                                <AdminIcon name="plus" size="sm" />
                                إضافة أول رابط
                            </button>
                        </div>

                        <div v-if="canManage" class="mt-6 flex items-center justify-end gap-3 border-t border-surface-100 pt-5">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-gold transition hover:bg-brand-700 active:scale-[0.98] disabled:opacity-60"
                                :disabled="socialForm.processing"
                                @click="submitSocial"
                            >
                                <AdminIcon :name="socialForm.processing ? 'refresh' : 'check'" size="sm" :class="{ 'animate-spin': socialForm.processing }" />
                                {{ socialForm.processing ? 'جارٍ الحفظ...' : 'حفظ الروابط' }}
                            </button>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <!-- Leaving a tab reloads the group, so confirm before dropping edits -->
        <Modal :show="!!pendingTab" max-width="md" @close="pendingTab = null">
            <div class="p-6 sm:p-7">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-amber-50 text-amber-600">
                        <AdminIcon name="alert" size="lg" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-surface-900">تغييرات غير محفوظة</h3>
                        <p class="mt-1.5 text-sm leading-6 text-surface-500">
                            لديك تعديلات في «<span class="font-semibold text-surface-900">{{ currentGroup.label }}</span>» لم تُحفظ بعد.
                            الانتقال إلى «<span class="font-semibold text-surface-900">{{ pendingTab?.label }}</span>» سيتجاهلها.
                        </p>
                    </div>
                </div>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-surface-500 transition hover:bg-surface-100 hover:text-surface-700"
                        @click="pendingTab = null"
                    >
                        البقاء هنا
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-surface-200 bg-white px-4 py-2 text-sm font-semibold text-surface-700 transition hover:bg-surface-50"
                        @click="discardAndSwitch"
                    >
                        <AdminIcon name="trash" size="sm" />
                        تجاهل التغييرات
                    </button>
                </div>
            </div>
        </Modal>

        <AdminToast />
    </AdminLayout>
</template>