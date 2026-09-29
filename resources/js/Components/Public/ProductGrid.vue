<script setup>
import ProductCard from './ProductCard.vue';
import EmptyState from './EmptyState.vue';
import SkeletonLoader from './SkeletonLoader.vue';

defineProps({
    products: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    hasFilters: { type: Boolean, default: false },
    emptyIcon: { type: String, default: 'search' },
    emptyTitle: { type: String, default: 'لا توجد منتجات بعد' },
    emptyDescription: { type: String, default: 'سنضيف منتجات جديدة قريباً، ترقّبوا القادم!' },
    resetText: { type: String, default: 'مسح الفلاتر' },
});

defineEmits(['quick-view', 'reset']);

const skeletonCards = [...Array(8)];
</script>

<template>
    <div v-if="loading" class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
        <div v-for="(_, index) in skeletonCards" :key="index" class="card-premium overflow-hidden">
            <SkeletonLoader className="aspect-[4/3] w-full rounded-none" />
            <div class="p-4 space-y-3">
                <SkeletonLoader className="h-3 w-1/3 rounded" />
                <SkeletonLoader className="h-4 w-3/4 rounded" />
                <SkeletonLoader className="h-5 w-1/2 rounded" />
            </div>
        </div>
    </div>

    <div v-else-if="products.length" class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
        <ProductCard
            v-for="product in products"
            :key="product.id"
            :product="product"
            @quick-view="(p) => $emit('quick-view', p)"
        />
    </div>

    <div v-else class="card-premium rounded-2xl p-8 sm:p-12">
        <EmptyState
            :icon="emptyIcon"
            :title="emptyTitle"
            :description="emptyDescription"
            :action-text="hasFilters ? resetText : ''"
            @action="$emit('reset')"
        >
            <template v-if="$slots['empty-action']" #action>
                <slot name="empty-action" />
            </template>
        </EmptyState>
    </div>
</template>