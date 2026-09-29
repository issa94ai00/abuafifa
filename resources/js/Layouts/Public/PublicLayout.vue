<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Public/Navbar.vue';
import Footer from '@/Components/Public/Footer.vue';
import FlashMessage from '@/Components/Public/FlashMessage.vue';
import WhatsAppButton from '@/Components/Public/WhatsAppButton.vue';
import ScrollToTop from '@/Components/Public/ScrollToTop.vue';

const props = defineProps({
    title: { type: String, default: '' },
    meta: { type: Object, default: () => ({}) },
    /** Run the page under a see-through navbar (pages that open on a hero). */
    transparentNav: { type: Boolean, default: false },
});

const page = usePage();

const seo = computed(() => {
    const meta = props.meta || {};
    const settings = page.props.settings || {};
    return {
        title: meta.title ? `${meta.title} — ${settings.site_name || 'معرض أبو عفيفة'}` : `${settings.site_name || 'معرض أبو عفيفة للمفروشات'}`,
        description: meta.description || 'معرض أبو عفيفة للمفروشات — صالة عرض أثاث فاخر في العقبة، الأردن',
        ogTitle: meta.og_title || meta.title,
        ogDescription: meta.og_description,
        ogImage: meta.og_image,
        canonical: meta.canonical,
        robots: meta.robots || 'index,follow',
    };
});
</script>

<template>
    <Head :title="seo.title">
        <meta name="description" :content="seo.description" />
        <meta name="robots" :content="seo.robots" />
        <meta property="og:title" :content="seo.ogTitle" />
        <meta property="og:description" :content="seo.ogDescription" />
        <meta v-if="seo.ogImage" property="og:image" :content="seo.ogImage" />
        <meta property="og:type" content="website" />
        <meta property="og:locale" content="ar_JO" />
        <link v-if="seo.canonical" rel="canonical" :href="seo.canonical" />
    </Head>

    <div class="flex flex-col min-h-screen">
        <Navbar :transparent="transparentNav" />

        <main class="flex-1">
            <slot />
        </main>

        <Footer />
    </div>

    <FlashMessage />
    <WhatsAppButton floating />
    <ScrollToTop />
</template>