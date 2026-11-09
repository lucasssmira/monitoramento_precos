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

const hoveredPoint = ref<(PricePoint & { x: number; y: number }) | null>(null);

const svgWidth = 900;
const svgHeight = 280;
const padding = { top: 20, right: 24, bottom: 20, left: 10 };

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

    const prices: number[] = [];
    for (const p of sortedHistory.value) {
        prices.push(p.price);
        if (p.installment_price !== null) {
            prices.push(p.installment_price);
        }
    }
    if (props.targetPrice > 0) {
        prices.push(props.targetPrice);
    }

    let min = Math.min(...prices);
    let max = Math.max(...prices);

    if (min === max) {
        min = Math.max(0, min - 20);
        max = max + 20;
    } else {
        const margin = (max - min) * 0.14;
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
        installmentY:
            item.installment_price !== null
                ? getY(item.installment_price)
                : null,
    }));
});

const buildSmoothPath = (coords: Array<{ x: number; y: number }>): string => {
    if (coords.length === 0) return '';
    if (coords.length === 1) {
        const pt = coords[0];
        return `M ${padding.left} ${pt.y.toFixed(1)} L ${padding.left + chartWidth} ${pt.y.toFixed(1)}`;
    }
    if (coords.length === 2) {
        return `M ${coords[0].x.toFixed(1)} ${coords[0].y.toFixed(1)} L ${coords[1].x.toFixed(1)} ${coords[1].y.toFixed(1)}`;
    }

    let d = `M ${coords[0].x.toFixed(1)} ${coords[0].y.toFixed(1)}`;
    for (let i = 0; i < coords.length - 1; i++) {
        const p0 = coords[i];
        const p1 = coords[i + 1];
        const midX = ((p0.x + p1.x) / 2).toFixed(1);
        d += ` C ${midX} ${p0.y.toFixed(1)}, ${midX} ${p1.y.toFixed(1)}, ${p1.x.toFixed(1)} ${p1.y.toFixed(1)}`;
    }
    return d;
};

const linePath = computed(() => {
    return buildSmoothPath(points.value.map((p) => ({ x: p.x, y: p.y })));
});

const installmentLinePath = computed(() => {
    const instCoords = points.value
        .filter((p) => p.installmentY !== null)
        .map((p) => ({ x: p.x, y: p.installmentY as number }));

    return buildSmoothPath(instCoords);
});

const areaPath = computed(() => {
    if (points.value.length === 0) {
        return '';
    }

    const baselineY = svgHeight;

    if (points.value.length === 1) {
        const pt = points.value[0];
        return `M ${padding.left} ${pt.y.toFixed(1)} L ${padding.left + chartWidth} ${pt.y.toFixed(1)} L ${padding.left + chartWidth} ${baselineY} L ${padding.left} ${baselineY} Z`;
    }

    const firstPt = points.value[0];
    const lastPt = points.value[points.value.length - 1];

    return `${linePath.value} L ${lastPt.x.toFixed(1)} ${baselineY} L ${firstPt.x.toFixed(1)} ${baselineY} Z`;
});

const targetPriceY = computed(() => {
    if (props.targetPrice <= 0) {
        return null;
    }
    return getY(props.targetPrice);
});

const yAxisLabels = computed(() => {
    const steps = 4;
    const { min, max } = priceBounds.value;
    const stepVal = (max - min) / steps;

    const labels = [];
    for (let i = steps; i >= 0; i--) {
        const val = min + stepVal * i;
        labels.push({
            value: val,
            y: getY(val),
        });
    }
    return labels;
});

const xAxisLabels = computed(() => {
    if (points.value.length === 0) return [];
    if (points.value.length === 1) {
        return [
            {
                label: formatDateShort(points.value[0].recorded_at),
                isLast: true,
            },
        ];
    }

    const count = Math.min(6, points.value.length);
    const result = [];
    for (let i = 0; i < count; i++) {
        const idx = Math.round((i / (count - 1)) * (points.value.length - 1));
        const pt = points.value[idx];
        result.push({
            label:
                i === count - 1
                    ? `Última (${formatDateShort(pt.recorded_at)})`
                    : formatDateShort(pt.recorded_at),
            isLast: i === count - 1,
        });
    }
    return result;
});

const activeTooltipPoint = computed(() => {
    if (hoveredPoint.value) {
        return hoveredPoint.value;
    }
    if (points.value.length > 0) {
        return points.value[points.value.length - 1];
    }
    return null;
});

const formatCurrency = (val: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(val);
};

const formatCurrencyCompact = (val: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        maximumFractionDigits: 0,
    }).format(val);
};

const formatDateShort = (dateStr: string): string => {
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('pt-BR', {
            day: '2-digit',
            month: 'short',
        })
            .format(d)
            .replace('.', '');
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
            hour: '2-digit',
            minute: '2-digit',
        }).format(d);
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <div>
        <!-- Empty State -->
        <div
            v-if="sortedHistory.length === 0"
            class="flex h-72 flex-col items-center justify-center rounded-lg border border-surface-border bg-canvas/50 text-center text-sm text-text-muted"
        >
            <p class="font-title text-title-md font-semibold text-text-primary">
                Sem variações registradas nos últimos {{ periodDays }} dias
            </p>
            <p class="mt-1 max-w-md text-body-sm text-text-secondary">
                O histórico armazena novas leituras sempre que houver oscilação
                de preço à vista, a prazo ou disponibilidade em estoque.
            </p>
        </div>

        <!-- Área do Gráfico SVG de Spline com Gradiente & Linhas de Referência -->
        <div v-else class="relative h-[360px] w-full pt-6 select-none">
            <!-- Marcadores do Eixo Y (Valores em R$) -->
            <div
                class="pointer-events-none absolute top-6 bottom-8 left-0 flex w-24 flex-col justify-between pr-3 text-right font-price text-[11px] text-text-muted tabular-nums"
            >
                <span
                    v-for="(item, idx) in yAxisLabels"
                    :key="idx"
                    :class="[
                        idx === yAxisLabels.length - 1
                            ? 'font-bold text-primary'
                            : '',
                    ]"
                >
                    {{ formatCurrencyCompact(item.value) }}
                </span>
            </div>

            <!-- Grade de Fundo & SVG -->
            <div class="relative ml-24 h-[300px]">
                <!-- Linhas Horizontais da Grade -->
                <div
                    class="pointer-events-none absolute inset-0 flex flex-col justify-between"
                >
                    <div
                        v-for="(_, idx) in yAxisLabels"
                        :key="idx"
                        class="w-full border-b border-surface-border/40"
                    />
                </div>

                <!-- Linha Pontilhada do Preço Alvo -->
                <div
                    v-if="targetPriceY !== null"
                    class="pointer-events-none absolute right-0 left-0 border-b-2 border-dotted border-amber/70"
                    :style="{
                        top: `${(targetPriceY / svgHeight) * 100}%`,
                    }"
                >
                    <span
                        class="absolute -top-5 right-2 rounded border border-amber/30 bg-surface px-1.5 py-0.5 font-mono text-[10px] font-bold text-amber"
                    >
                        Alvo: {{ formatCurrency(targetPrice) }}
                    </span>
                </div>

                <!-- Gráfico SVG Vetorial de Alta Fidelidade -->
                <svg
                    class="size-full overflow-visible"
                    :viewBox="`0 0 ${svgWidth} ${svgHeight}`"
                    preserveAspectRatio="none"
                >
                    <defs>
                        <linearGradient
                            id="emeraldGradient"
                            x1="0%"
                            y1="0%"
                            x2="0%"
                            y2="100%"
                        >
                            <stop
                                offset="0%"
                                stop-color="#34D399"
                                stop-opacity="0.32"
                            />
                            <stop
                                offset="60%"
                                stop-color="#34D399"
                                stop-opacity="0.08"
                            />
                            <stop
                                offset="100%"
                                stop-color="#34D399"
                                stop-opacity="0.0"
                            />
                        </linearGradient>
                    </defs>

                    <!-- Área Preenchida Preço à Vista (PIX) -->
                    <path
                        v-if="areaPath"
                        :d="areaPath"
                        fill="url(#emeraldGradient)"
                    />

                    <!-- Linha Secundária Tracejada: Preço a Prazo (Soft Indigo) -->
                    <path
                        v-if="installmentLinePath"
                        :d="installmentLinePath"
                        fill="none"
                        stroke="#818CF8"
                        stroke-width="2"
                        stroke-dasharray="6,6"
                        stroke-linecap="round"
                    />

                    <!-- Linha Primária Sólida: Preço à Vista PIX (Soft Emerald) -->
                    <path
                        v-if="linePath"
                        :d="linePath"
                        fill="none"
                        stroke="#34D399"
                        stroke-width="3"
                        stroke-linecap="round"
                    />

                    <!-- Pontos de Amostragem no Gráfico -->
                    <g
                        v-for="(pt, idx) in points"
                        :key="pt.id"
                        class="cursor-pointer"
                        @mouseenter="hoveredPoint = pt"
                        @mouseleave="hoveredPoint = null"
                    >
                        <circle
                            :cx="pt.x"
                            :cy="pt.y"
                            r="14"
                            fill="transparent"
                        />
                        <template v-if="idx === points.length - 1">
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                r="6"
                                fill="#34D399"
                                class="animate-pulse"
                            />
                            <circle
                                :cx="pt.x"
                                :cy="pt.y"
                                r="10"
                                fill="none"
                                stroke="#34D399"
                                stroke-width="1.5"
                                opacity="0.5"
                            />
                        </template>
                        <circle
                            v-else
                            :cx="pt.x"
                            :cy="pt.y"
                            :r="hoveredPoint?.id === pt.id ? 6 : 4"
                            fill="#131B2E"
                            stroke="#34D399"
                            stroke-width="2"
                        />
                    </g>
                </svg>

                <!-- Tooltip Interativo -->
                <div
                    v-if="activeTooltipPoint"
                    class="pointer-events-none absolute right-2 bottom-10 z-20 rounded-lg border border-primary/50 bg-surface-elevated/95 p-3 shadow-xl backdrop-blur-md"
                >
                    <div
                        class="mb-1 flex items-center gap-1.5 font-mono text-[11px] text-text-muted"
                    >
                        <span
                            class="size-1.5 animate-ping rounded-full bg-primary"
                        />
                        {{ formatFullDate(activeTooltipPoint.recorded_at) }}
                    </div>
                    <div
                        class="font-price text-price-md font-bold text-primary tabular-nums"
                    >
                        {{ formatCurrency(activeTooltipPoint.price) }}
                        <span class="text-xs font-normal text-text-muted">
                            (PIX)
                        </span>
                    </div>
                    <div
                        v-if="activeTooltipPoint.installment_price"
                        class="font-mono text-[11px] text-indigo tabular-nums"
                    >
                        A prazo:
                        {{
                            formatCurrency(activeTooltipPoint.installment_price)
                        }}
                    </div>
                    <div
                        v-if="
                            targetPrice > 0 &&
                            activeTooltipPoint.price <= targetPrice
                        "
                        class="mt-1 flex items-center gap-1 text-[10px] font-semibold text-amber"
                    >
                        <span>
                            {{
                                formatCurrency(
                                    targetPrice - activeTooltipPoint.price,
                                )
                            }}
                            abaixo da meta!
                        </span>
                    </div>
                </div>
            </div>

            <!-- Marcadores do Eixo X (Datas) -->
            <div
                class="ml-24 flex justify-between border-t border-surface-border pt-3 font-mono text-[11px] text-text-muted"
            >
                <span
                    v-for="(item, idx) in xAxisLabels"
                    :key="idx"
                    :class="[item.isLast ? 'font-bold text-primary' : '']"
                >
                    {{ item.label }}
                </span>
            </div>
        </div>
    </div>
</template>
