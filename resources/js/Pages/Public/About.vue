<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import SectionHeader from '@/Components/Public/SectionHeader.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    about: { type: Object, default: () => ({}) },
    seo: { type: Object, default: () => ({}) },
});

const page = usePage();
const settings = computed(() => page.props.settings || {});

const intro = computed(() => props.about?.intro || '');

const whatsappNumber = computed(
    () => String(settings.value.whatsapp || '').replace(/\D/g, '')
);
const whatsappLink = computed(() => `https://wa.me/${whatsappNumber.value}`);

const milestones = [
    { value: '+15', label: 'سنة خبرة', icon: 'clock' },
    { value: '+50', label: 'تصميماً فاخراً', icon: 'sparkles' },
    { value: '+1000', label: 'عميل سعيد', icon: 'heart' },
    { value: '100%', label: 'التزام بالجودة', icon: 'shield' },
];

const timeline = [
    {
        year: '2009',
        title: 'البداية',
        text: 'انطلاقة المعرض من ورشة صغيرة بحب لصناعة الأثاث الفاخر وفريق يؤمن بالحرفية.',
    },
    {
        year: '2013',
        title: 'توسع أول',
        text: 'انتقلنا إلى صالة عرض أرسع لنستقبل أكثر من مائة تصميم في آن واحد.',
    },
    {
        year: '2018',
        title: 'توسع جديد',
        text: 'افتتحنا فرعنا الثاني ووسّعنا تشكيلتنا ليشمل التصميم الداخلي الكامل.',
    },
    {
        year: '2023',
        title: 'ريادة رقمية',
        text: 'أطلقنا متجرنا الإلكتروني لنوصلك التشكيلة الفاخرة إلى باب منزلك أينما كنت.',
    },
];

const values = [
    {
        icon: 'shield',
        title: 'جودة أصيلة',
        text: 'نختار الخامات بعناية فائقة ونحرص على إتقان كل تفصيلة، فالجودة لدينا ليست خياراً بل التزام.',
        image: '/images/home/craft-leather-800.webp',
        alt: 'خامات جلدية فاخرة في معرض أبو عفيفة',
    },
    {
        icon: 'sparkles',
        title: 'ذوق يعكس شخصيتك',
        text: 'تصاميم تجمع بين الأصالة الحلبية والروح العصرية، لتجد دائماً ما يعبّر عنك في كل قطعة.',
        image: '/images/home/craft-textile-800.webp',
        alt: 'أقمشة ووسائد بألوان دافئة في المعرض',
    },
    {
        icon: 'truck',
        title: 'خدمة ترافقك',
        text: 'من الاستشارة المجانية حتى التوصيل والتركيب، نرافقك في كل خطوة لتسعد بمنزلك.',
        image: '/images/home/craft-chair-800.webp',
        alt: 'كرسي منجّد بتصميم عصري في معرض أبو عفيفة',
    },
    {
        icon: 'heart',
        title: 'ثقة تدوم',
        text: 'أكثر من خمسة عشر عاماً في خدمة العائلات، وكثير من عملائنا باتوا أصدقاء.',
        image: '/images/home/about-showroom-800.webp',
        alt: 'صالة عرض معرض أبو عفيفة للمفروشات',
    },
];

const testimonials = [
    {
        name: 'أحمد الخطيب',
        city: 'العقبة',
        text: 'تجربة رائعة من البداية حتى النهاية. الصالة ساعدتنا في اختيار أثاث يناسب مساحتنا وذوقنا، والجودة أفضل مما توقّعنا.',
        rating: 5,
    },
    {
        name: 'سارة الدوسري',
        city: 'عمّان',
        text: 'أثاث الصالة متين وفخم في نفس الوقت. مرّ سنة وما زال كما هو جديد. أنصح الجميع بزيارتهم.',
        rating: 5,
    },
    {
        name: 'محمد القاسم',
        city: 'الزرقاء',
        text: 'خدمة ما بعد البيع ممتازة، وفريقهم محترف من أول زيارة حتى التركيب. من أفضل معارض الأثاث في العقبة بلا منازع.',
        rating: 5,
    },
];

const showroomImages = [
    { src: '/images/home/about-showroom-800.webp', alt: 'صالة عرض معرض أبو عفيفة — كنبات فاخرة' },
    { src: '/images/home/craft-leather-800.webp', alt: 'خامات جلدية طبيعية في المعرض' },
    { src: '/images/home/craft-textile-800.webp', alt: 'تشكيلة أقمشة وألوان متنوعة' },
    { src: '/images/home/craft-chair-800.webp', alt: 'كراسي منجّدة بتصاميم عصرية' },
];

function getInitials(name) {
    return name.split(' ').map(w => w[0]).join('').slice(0, 2);
}
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <section class="relative bg-gradient-premium overflow-hidden">
            <div class="absolute inset-0 opacity-[0.07] bg-cover bg-center" style="background-image: url('/images/home/texture-cta-1200.jpg')"></div>

            <!-- Floating decorative shapes -->
            <div class="absolute top-20 right-[10%] w-2 h-2 rounded-full bg-gold-400/40 animate-pulse" aria-hidden="true"></div>
            <div class="absolute top-40 left-[15%] w-1.5 h-1.5 rounded-full bg-gold-300/30 animate-pulse" style="animation-delay: 1s" aria-hidden="true"></div>
            <div class="absolute bottom-32 right-[20%] w-1 h-1 rounded-full bg-gold-500/50 animate-pulse" style="animation-delay: 0.5s" aria-hidden="true"></div>
            <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[40rem] h-[40rem] rounded-full bg-gold-500/8 blur-3xl" aria-hidden="true"></div>

            <!-- Decorative golden line -->
            <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-gold-500/20 to-transparent" aria-hidden="true"></div>

            <div class="section-container pt-32 pb-20 sm:pt-40 sm:pb-28 text-center relative">
                <RevealOnScroll animation="fade-in-up">
                    <span class="inline-flex items-center gap-2 text-gold-400 text-sm font-medium mb-4">
                        <span class="h-px w-8 bg-gradient-to-l from-gold-500 to-transparent"></span>
                        قصتنا
                        <span class="h-px w-8 bg-gradient-to-r from-gold-500 to-transparent"></span>
                    </span>
                    <h1 class="font-display text-3xl sm:text-5xl md:text-6xl font-bold text-white leading-tight mb-5">
                        معرض أبو عفيفة
                        <span class="block text-transparent bg-clip-text bg-gradient-to-l from-gold-300 to-gold-500 mt-2">للمفروشات والأثاث الفاخر</span>
                    </h1>
                    <p class="max-w-3xl mx-auto text-surface-300 text-base sm:text-lg leading-relaxed">
                        قصة بدأت من حب التفاصيل، تحوّلت إلى فنًّا في صناعة الأثاث
                    </p>
                </RevealOnScroll>
            </div>

            <!-- Scroll indicator -->
            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-surface-400/60" aria-hidden="true">
                <span class="text-xs tracking-widest uppercase">اكتشف</span>
                <div class="w-5 h-8 rounded-full border border-surface-400/30 flex justify-center pt-1.5">
                    <div class="w-1 h-2 rounded-full bg-gold-400/60 animate-bounce"></div>
                </div>
            </div>

            <!-- Bottom edge fade -->
            <div class="absolute bottom-0 inset-x-0 h-24 bg-gradient-to-t from-surface-50 to-transparent" aria-hidden="true"></div>
        </section>

        <!-- Intro -->
        <section class="section-container py-16 sm:py-24">
            <RevealOnScroll animation="fade-in-up">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div class="relative">
                        <!-- Decorative frame -->
                        <div class="absolute -inset-3 rounded-[2rem] border border-gold-200/40" aria-hidden="true"></div>
                        <div class="absolute -inset-1 rounded-[1.75rem] bg-gradient-to-br from-gold-100/20 to-transparent" aria-hidden="true"></div>

                        <div class="relative rounded-3xl overflow-hidden shadow-premium-lg">
                            <img
                                src="/images/home/about-showroom-800.webp"
                                srcset="/images/home/about-showroom-800.webp 800w, /images/home/about-showroom-1400.webp 1400w"
                                sizes="(min-width: 1024px) 50vw, 100vw"
                                alt="أثاث فاخر في معرض أبو عفيفة للمفروشات"
                                loading="lazy"
                                decoding="async"
                                class="w-full h-[380px] sm:h-[460px] object-cover"
                            />
                            <!-- Image overlay badge -->
                            <div class="absolute bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-auto">
                                <div class="inline-flex items-center gap-2 bg-surface-950/70 backdrop-blur-sm text-white text-xs font-medium px-4 py-2 rounded-full">
                                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                                    صالة العرض مفتوحة يومياً
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <SectionHeader
                            eyebrow="من نحن"
                            title="نحن نصنع بيوتاً لا مجرد أثاث"
                        />
                        <p class="text-surface-600 leading-relaxed mb-6">
                            {{ intro }}
                        </p>
                        <p class="text-surface-600 leading-relaxed">
                            في معرض أبو عفيفة نؤمن أن الأثاث الجيد يلمس تفاصيل حياتك اليومية،
                            لذلك نمزج الحرفة اليدوية المتوارثة مع الرؤية التصميمية العصرية،
                            لنقدم قطعاً تجمع بين المتانة والجمال والراحة.
                        </p>

                        <!-- Stats bar -->
                        <div class="glass-panel rounded-2xl p-5 sm:p-6 mt-10">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
                                <div v-for="stat in milestones" :key="stat.label" class="text-center group">
                                    <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-brand-50 ring-1 ring-brand-100 flex items-center justify-center text-brand-600 transition-all duration-300 group-hover:bg-brand-100 group-hover:shadow-gold">
                                        <Icon :name="stat.icon" class="w-5 h-5" />
                                    </div>
                                    <span class="block font-display text-2xl sm:text-3xl font-bold text-brand-700">{{ stat.value }}</span>
                                    <span class="text-xs sm:text-sm text-surface-500 mt-1">{{ stat.label }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </RevealOnScroll>
        </section>

        <!-- Brand Timeline -->
        <section class="bg-surface-100/60 py-16 sm:py-20 border-y border-surface-200/60">
            <div class="section-container">
                <SectionHeader
                    eyebrow="مسيرة النجاح"
                    title="كيف وصلنا إلى هنا؟"
                    subtitle="خطوات واثقة نحو التميز، كل خطوة صنعها الالتزام بعملائنا"
                />

                <div class="relative mt-12">
                    <!-- Timeline line -->
                    <div class="hidden sm:block absolute top-6 left-0 right-0 h-px bg-gradient-to-r from-transparent via-brand-300 to-transparent" aria-hidden="true"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                        <RevealOnScroll
                            v-for="(item, index) in timeline"
                            :key="item.year"
                            animation="fade-in-up"
                            :delay="index * 120"
                        >
                            <div class="relative text-center sm:text-center group">
                                <!-- Timeline dot -->
                                <div class="hidden sm:flex w-12 h-12 mx-auto mb-4 rounded-full bg-white ring-2 ring-brand-200 items-center justify-center relative z-10 transition-all duration-300 group-hover:ring-brand-400 group-hover:shadow-gold">
                                    <span class="font-display font-bold text-sm text-brand-700">{{ item.year }}</span>
                                </div>

                                <!-- Mobile year badge -->
                                <div class="sm:hidden inline-flex items-center gap-2 bg-brand-50 ring-1 ring-brand-100 rounded-full px-4 py-1.5 mb-4">
                                    <span class="font-display font-bold text-sm text-brand-700">{{ item.year }}</span>
                                </div>

                                <h3 class="font-display font-bold text-lg text-surface-900 mb-2">{{ item.title }}</h3>
                                <p class="text-sm text-surface-500 leading-relaxed max-w-[220px] mx-auto">{{ item.text }}</p>
                            </div>
                        </RevealOnScroll>
                    </div>
                </div>
            </div>
        </section>

        <!-- Values -->
        <section class="py-16 sm:py-20">
            <div class="section-container">
                <SectionHeader
                    eyebrow="قيمنا"
                    title="ما الذي يميزنا؟"
                    subtitle="أربع ركائز أساسية توجّه كل قطعة نصنعها وكل خدمة نقدّمها"
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                    <RevealOnScroll
                        v-for="(value, index) in values"
                        :key="value.title"
                        animation="fade-in-up"
                        :delay="index * 80"
                    >
                        <article class="group h-full bg-white rounded-3xl overflow-hidden border border-surface-100 shadow-premium transition-all duration-300 hover:shadow-premium-lg hover:-translate-y-1">
                            <div class="relative aspect-[4/3] overflow-hidden bg-surface-100">
                                <img
                                    :src="value.image"
                                    :alt="value.alt"
                                    loading="lazy"
                                    decoding="async"
                                    class="img-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                />
                                <!-- Icon badge -->
                                <div class="absolute top-4 right-4 inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/90 backdrop-blur-sm text-brand-600 shadow-premium transition-transform duration-300 group-hover:scale-110">
                                    <Icon :name="value.icon" class="w-5 h-5" />
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="font-display font-bold text-lg text-surface-900 mb-2">{{ value.title }}</h3>
                                <p class="text-sm text-surface-500 leading-relaxed">{{ value.text }}</p>
                            </div>
                        </article>
                    </RevealOnScroll>
                </div>
            </div>
        </section>

        <!-- Showroom Gallery Preview -->
        <section class="bg-surface-100/60 py-16 sm:py-20 border-y border-surface-200/60">
            <div class="section-container">
                <SectionHeader
                    eyebrow="من صالة العرض"
                    title="لحظات من داخل المعرض"
                    subtitle="لقطات حقيقية تعكس جودة وفخامة تشكيلتنا"
                />

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <RevealOnScroll
                        v-for="(img, index) in showroomImages"
                        :key="index"
                        animation="scale-in"
                        :delay="index * 100"
                    >
                        <div class="relative aspect-square rounded-2xl overflow-hidden group cursor-pointer shadow-premium">
                            <img
                                :src="img.src"
                                :alt="img.alt"
                                loading="lazy"
                                decoding="async"
                                class="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-surface-950/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="absolute bottom-0 inset-x-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                <span class="text-white text-xs font-medium">{{ img.alt }}</span>
                            </div>
                        </div>
                    </RevealOnScroll>
                </div>

                <div class="text-center mt-8">
                    <Link
                        href="/gallery"
                        class="inline-flex items-center gap-2 font-medium text-sm text-brand-600 hover:text-brand-700 transition-colors group"
                    >
                        <span>عرض معرض الصور كاملاً</span>
                        <Icon name="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        <section class="relative bg-gradient-premium overflow-hidden py-16 sm:py-20">
            <div class="absolute -top-32 right-0 w-[28rem] h-[28rem] rounded-full bg-gold-500/10 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-32 left-0 w-[24rem] h-[24rem] rounded-full bg-brand-500/8 blur-3xl" aria-hidden="true"></div>

            <div class="relative section-container">
                <SectionHeader
                    eyebrow="ماذا يقول عملاؤنا"
                    title="ثقة عملائنا هي فخورنا"
                    dark
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
                    <RevealOnScroll
                        v-for="(testimonial, index) in testimonials"
                        :key="testimonial.name"
                        animation="fade-in-up"
                        :delay="index * 100"
                    >
                        <div class="glass-panel rounded-2xl p-6 sm:p-7 h-full flex flex-col relative">
                            <!-- Decorative quote mark -->
                            <div class="absolute top-4 left-4 text-gold-500/20 font-display text-6xl leading-none select-none" aria-hidden="true">"</div>

                            <!-- Stars -->
                            <div class="flex gap-1 text-gold-400 mb-4 relative z-10">
                                <Icon
                                    v-for="n in testimonial.rating"
                                    :key="n"
                                    name="star"
                                    class="w-4 h-4"
                                />
                            </div>
                            <p class="text-surface-300 leading-relaxed text-sm flex-1 relative z-10">
                                "{{ testimonial.text }}"
                            </p>
                            <div class="mt-5 pt-4 border-t border-white/10 flex items-center gap-3 relative z-10">
                                <!-- Avatar -->
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-400 to-gold-500 flex items-center justify-center text-white text-xs font-bold font-display shrink-0">
                                    {{ getInitials(testimonial.name) }}
                                </div>
                                <div>
                                    <span class="font-display font-semibold text-white text-sm block">{{ testimonial.name }}</span>
                                    <span class="text-surface-400 text-xs flex items-center gap-1">
                                        <Icon name="map-pin" class="w-3 h-3" />
                                        {{ testimonial.city }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </RevealOnScroll>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="section-container py-16 sm:py-20">
            <RevealOnScroll animation="fade-in-up">
                <div class="relative bg-gradient-gold rounded-3xl overflow-hidden shadow-premium-lg">
                    <div
                        class="absolute inset-0 bg-cover bg-center opacity-15 mix-blend-overlay"
                        style="background-image: url('/images/home/texture-cta-1200.jpg')"
                        aria-hidden="true"
                    ></div>
                    <div class="relative px-8 sm:px-12 lg:px-16 py-14 sm:py-20 text-center">
                        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-surface-950 mb-4">
                            تعالَ وشاهد التشكيلة بنفسك
                        </h2>
                        <p class="text-surface-800 max-w-2xl mx-auto mb-8 leading-relaxed">
                            فريقنا جاهز لاستقبالك في معرضنا ومساعدتك على اختيار القطع المثالية لمنزلك.
                        </p>

                        <!-- Contact details -->
                        <div class="flex flex-wrap justify-center gap-6 mb-8 text-sm text-surface-800">
                            <div v-if="settings.address" class="flex items-center gap-2">
                                <Icon name="map-pin" class="w-4 h-4 text-surface-600" />
                                <span>{{ settings.address }}</span>
                            </div>
                            <div v-if="settings.working_hours" class="flex items-center gap-2">
                                <Icon name="clock" class="w-4 h-4 text-surface-600" />
                                <span>{{ settings.working_hours }}</span>
                            </div>
                            <div v-if="settings.phone" class="flex items-center gap-2">
                                <Icon name="phone" class="w-4 h-4 text-surface-600" />
                                <span dir="ltr">{{ settings.phone }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-center gap-4">
                            <a
                                v-if="whatsappNumber"
                                :href="whatsappLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn-dark shadow-premium"
                            >
                                <Icon name="whatsapp" class="w-5 h-5" />
                                تواصل عبر واتساب
                            </a>
                            <Link href="/contact" class="btn-white shadow-premium">
                                نموذج التواصل
                            </Link>
                            <Link href="/products" class="btn-white shadow-premium">
                                تصفح المنتجات
                            </Link>
                        </div>
                    </div>
                </div>
            </RevealOnScroll>
        </section>
    </PublicLayout>
</template>
