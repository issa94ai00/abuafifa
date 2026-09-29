export function usePrice() {
    function formatPrice(amount, currency = 'JOD') {
        if (amount === null || amount === undefined) return null;

        const symbols = {
            JOD: 'د.أ',
            SYP: 'ل.س',
            USD: '$',
            EUR: '€',
        };

        const symbol = symbols[currency] || currency;
        const formatted = new Intl.NumberFormat('ar-JO', {
            maximumFractionDigits: 0,
        }).format(amount);

        return `${formatted} ${symbol}`;
    }

    function discountPercentage(original, discounted) {
        if (!original || !discounted || original <= discounted) return 0;
        return Math.round(((original - discounted) / original) * 100);
    }

    return { formatPrice, discountPercentage };
}
