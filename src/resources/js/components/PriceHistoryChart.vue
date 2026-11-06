<script setup lang="ts">
import { computed, ref } from 'vue';

export interface PricePoint {
    id: number;
    price: number;
    installment_price: number | null;
    in_stock: boolean;
    recorded_at: string;
}

const props = defineProps<{
    history: PricePoint[];
    targetPrice: number;
    periodDays: number;
}>();

const hoveredPoint = ref<PricePoint | null>(null);
const hoveredCoords = ref<{ x: number; y: number } | null>(null);

const svgWidth = 800;
const svgHeight = 320;
const padding = { top: 30, right: 30, bottom: 40, left: 70 };

const chartWidth = svgWidth - padding.left - padding.right;
const chartHeight = svgHeight - padding.top - padding.bottom;

const sortedHistory = computed(() => {
    return [...props.history].sort(
        (a, b) =>
            new Date(a.recorded_at).getTime() -
            new Date(b.recorded_at).getTime(),
    );
});

const priceBounds = computed(() => {
    if (sortedHistory.value.length === 0) {
        const base = props.targetPrice > 0 ? props.targetPrice : 100;
        return { min: base * 0.8, max: base * 1.2 };
    }

    const prices = sortedHistory.value.map((p) => p.price);
    if (props.targetPrice > 0) {
        prices.push(props.targetPrice);
    }

    let min = Math.min(...prices);
    let max = Math.max(...prices);

    if (min === max) {
        min = Math.max(0, min - 20);
        max = max + 20;
    } else {
        const margin = (max - min) * 0.12;
        min = Math.max(0, min - margin);
        max = max + margin;
    }

    return { min, max };
});

const timeBounds = computed(() => {
    if (sortedHistory.value.length === 0) {
        const now = Date.now();
        return { min: now - props.periodDays * 86400000, max: now };
    }

    const times = sortedHistory.value.map((p) =>
        new Date(p.recorded_at).getTime(),
    );
    const min = Math.min(...times);
    const max = Math.max(...times);

    // If all readings are from the exact same timestamp, span 1 hour
    if (min === max) {
        return { min: min - 3600000, max: max + 3600000 };
    }

    return { min, max };
});

const getX = (dateStr: string): number => {
    const time = new Date(dateStr).getTime();
    const { min, max } = timeBounds.value;
    if (max === min) {
        return padding.left + chartWidth / 2;
    }
    const ratio = Math.max(0, Math.min(1, (time - min) / (max - min)));
    return padding.left + ratio * chartWidth;
};

const getY = (price: number): number => {
    const { min, max } = priceBounds.value;
    if (max === min) {
        return padding.top + chartHeight / 2;
    }
    const ratio = Math.max(0, Math.min(1, (price - min) / (max - min)));
    return padding.top + (1 - ratio) * chartHeight;
};

const points = computed(() => {
    return sortedHistory.value.map((item) => ({
        ...item,
        x: getX(item.recorded_at),
        y: getY(item.price),
    }));
});

const linePath = computed(() => {
    if (points.value.length === 0) {
        return '';
    }

    if (points.value.length === 1) {
        const pt = points.value[0];
        return `M ${padding.left} ${pt.y} L ${padding.left + chartWidth} ${pt.y}`;
    }

    return points.value
        .map(
            (pt, idx) =>
                `${idx === 0 ? 'M' : 'L'} ${pt.x.toFixed(1)} ${pt.y.toFixed(1)}`,
        )
        .join(' ');
});

const areaPath = computed(() => {
    if (points.value.length === 0) {
        return '';
    }

    const baselineY = padding.top + chartHeight;

    if (points.value.length === 1) {
        const pt = points.value[0];
        return `M ${padding.left} ${pt.y} L ${padding.left + chartWidth} ${pt.y} L ${padding.left + chartWidth} ${baselineY} L ${padding.left} ${baselineY} Z`;
    }

    const firstPt = points.value[0];
    const lastPt = points.value[points.value.length - 1];

    const mainLine = points.value
        .map(
            (pt, idx) =>
                `${idx === 0 ? 'M' : 'L'} ${pt.x.toFixed(1)} ${pt.y.toFixed(1)}`,
        )
        .join(' ');

    return `${mainLine} L ${lastPt.x.toFixed(1)} ${baselineY} L ${firstPt.x.toFixed(1)} ${baselineY} Z`;
});

const targetPriceY = computed(() => {
    if (props.targetPrice <= 0) {
        return null;
    }
    return getY(props.targetPrice);
});

const gridLines = computed(() => {
    const steps = 4;
    const { min, max } = priceBounds.value;
    const stepVal = (max - min) / steps;

    const lines = [];
    for (let i = 0; i <= steps; i++) {
        const val = min + stepVal * i;
        lines.push({
            value: val,
            y: getY(val),
        });
    }
    return lines;
});

const formatCurrency = (val: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(val);
};

const formatDateShort = (dateStr: string): string => {
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
        }).format(d);
    } catch {
        return dateStr;
    }
};

const formatFullDate = (dateStr: string): string => {
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(d);
    } catch {
        return dateStr;
    }
};

const handleMouseEnter = (pt: (typeof points.value)[number]): void => {
    hoveredPoint.value = pt;
    hoveredCoords.value = { x: pt.x, y: pt.y };
};

const handleMouseLeave = (): void => {
    hoveredPoint.value = null;
    hoveredCoords.value = null;
};
</script>

<template>
    <div class="relative w-full overflow-hidden rounded-lg border bg-card p-4">
        <!-- Legenda e Status -->
        <div
            class="mb-4 flex flex-wrap items-center justify-between gap-3 text-xs text-muted-foreground"
        >
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <span class="inline-block size-3 rounded-full bg-primary" />
                    <span>Preço à vista</span>
                </div>
                <div v-if="targetPrice > 0" class="flex items-center gap-1.5">
                    <span
                        class="inline-block h-0.5 w-3 border-t-2 border-dashed border-amber-500"
                    />
                    <span>Preço Alvo ({{ formatCurrency(targetPrice) }})</span>
                </div>
            </div>

            <div v-if="sortedHistory.length > 0" class="text-xs">
                {{ sortedHistory.length }} registro(s) no período de
                {{ periodDays }} dias
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="sortedHistory.length === 0"
            class="flex h-64 flex-col items-center justify-center text-center text-sm text-muted-foreground"
        >
            <p class="font-medium text-foreground">
                Sem variações registradas no período de {{ periodDays }} dias
            </p>
            <p class="mt-1 text-xs">
                O histórico armazena novas leituras sempre que houver alteração
                de preço ou estoque.
            </p>
        </div>

        <!-- SVG Gráfico -->
        <div v-else class="relative w-full">
            <svg
                :viewBox="`0 0 ${svgWidth} ${svgHeight}`"
                class="size-full overflow-visible"
                preserveAspectRatio="xMidYMid meet"
            >
                <defs>
                    <linearGradient
                        id="priceGradient"
                        x1="0"
                        y1="0"
                        x2="0"
                        y2="1"
                    >
                        <stop
                            offset="0%"
                            stop-color="var(--color-primary, #3b82f6)"
                            stop-opacity="0.3"
                        />
                        <stop
                            offset="100%"
                            stop-color="var(--color-primary, #3b82f6)"
                            stop-opacity="0.0"
                        />
                    </linearGradient>
                </defs>

                <!-- Linhas de Grade e Valores do Eixo Y -->
                <g class="grid-lines">
                    <g v-for="grid in gridLines" :key="grid.y">
                        <line
                            :x1="padding.left"
                            :y1="grid.y"
                            :x2="svgWidth - padding.right"
                            :y2="grid.y"
                            stroke="currentColor"
                            stroke-opacity="0.1"
                            stroke-dasharray="3,3"
                        />
                        <text
                            :x="padding.left - 8"
                            :y="grid.y + 3"
                            text-anchor="end"
                            class="fill-muted-foreground text-[10px]"
                        >
                            {{ formatCurrency(grid.value) }}
                        </text>
                    </g>
                </g>

                <!-- Linha de Referência do Preço Alvo -->
                <g v-if="targetPriceY !== null">
                    <line
                        :x1="padding.left"
                        :y1="targetPriceY"
                        :x2="svgWidth - padding.right"
                        :y2="targetPriceY"
                        stroke="#f59e0b"
                        stroke-width="1.5"
                        stroke-dasharray="4,4"
                    />
                    <text
                        :x="svgWidth - padding.right"
                        :y="targetPriceY - 6"
                        text-anchor="end"
                        class="fill-amber-600 text-[10px] font-semibold dark:fill-amber-400"
                    >
                        Meta
                    </text>
                </g>

                <!-- Área Preenchida com Gradiente -->
                <path
                    v-if="areaPath"
                    :d="areaPath"
                    fill="url(#priceGradient)"
                />

                <!-- Linha Principal de Preço -->
                <path
                    v-if="linePath"
                    :d="linePath"
                    fill="none"
                    stroke="var(--color-primary, #3b82f6)"
                    stroke-width="2.5"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />

                <!-- Pontos / Leituras no Gráfico -->
                <g class="points">
                    <g
                        v-for="pt in points"
                        :key="pt.id"
                        class="cursor-pointer"
                        @mouseenter="handleMouseEnter(pt)"
                        @mouseleave="handleMouseLeave"
                    >
                        <!-- Ponto invisível maior para facilitar hover em toque/mouse -->
                        <circle
                            :cx="pt.x"
                            :cy="pt.y"
                            r="12"
                            fill="transparent"
                        />
                        <!-- Ponto visível -->
                        <circle
                            :cx="pt.x"
                            :cy="pt.y"
                            :r="hoveredPoint?.id === pt.id ? 5.5 : 3.5"
                            fill="var(--color-card, #ffffff)"
                            stroke="var(--color-primary, #3b82f6)"
                            stroke-width="2.5"
                            class="transition-all duration-150"
                        />
                    </g>
                </g>

                <!-- Rótulos do Eixo X (Datas) -->
                <g class="x-axis-labels">
                    <text
                        v-if="points.length > 0"
                        :x="points[0].x"
                        :y="svgHeight - 12"
                        text-anchor="start"
                        class="fill-muted-foreground text-[10px]"
                    >
                        {{ formatDateShort(points[0].recorded_at) }}
                    </text>
                    <text
                        v-if="points.length > 1"
                        :x="points[points.length - 1].x"
                        :y="svgHeight - 12"
                        text-anchor="end"
                        class="fill-muted-foreground text-[10px]"
                    >
                        {{
                            formatDateShort(
                                points[points.length - 1].recorded_at,
                            )
                        }}
                    </text>
                </g>
            </svg>

            <!-- Tooltip Flutuante -->
            <div
                v-if="hoveredPoint && hoveredCoords"
                class="pointer-events-none absolute z-20 rounded-md border bg-popover px-3 py-2 text-xs shadow-md"
                :style="{
                    left: `${Math.min(Math.max(hoveredCoords.x - 70, 10), chartWidth - 80)}px`,
                    top: `${Math.max(hoveredCoords.y - 75, 10)}px`,
                }"
            >
                <p class="font-semibold text-foreground">
                    {{ formatCurrency(hoveredPoint.price) }}
                </p>
                <p
                    v-if="hoveredPoint.installment_price"
                    class="text-muted-foreground"
                >
                    A prazo:
                    {{ formatCurrency(hoveredPoint.installment_price) }}
                </p>
                <p class="text-[10px] text-muted-foreground">
                    {{ formatFullDate(hoveredPoint.recorded_at) }}
                </p>
                <p
                    :class="[
                        'mt-0.5 text-[10px] font-medium',
                        hoveredPoint.in_stock
                            ? 'text-green-600 dark:text-green-400'
                            : 'text-red-600 dark:text-red-400',
                    ]"
                >
                    {{ hoveredPoint.in_stock ? 'Em estoque' : 'Esgotado' }}
                </p>
            </div>
        </div>
    </div>
</template>
