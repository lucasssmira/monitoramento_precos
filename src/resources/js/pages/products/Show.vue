<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    Cpu,
    ExternalLink,
    Gamepad2,
    Headphones,
    History,
    Info,
    Monitor,
    Package,
    Pencil,
    RefreshCw,
    Smartphone,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PriceHistoryChart, {
    type PricePoint,
} from '@/components/PriceHistoryChart.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import {
    checkNow as checkNowRoute,
    index as productsIndex,
    show as showRoute,
    update as updateRoute,
} from '@/routes/products';

interface ProductDetail {
    id: number;
    url: string;
    normalized_url: string;
    domain: string;
    title: string | null;
    nickname: string | null;
    category: string;
    image_url: string | null;
    target_price: number;
    current_price: number | null;
    installment_price: number | null;
    in_stock: boolean;
    notify_on_stock: boolean;
    status: string;
    status_label: string;
    last_checked_at: string | null;
    last_manual_check_at: string | null;
    created_at: string | null;
}

interface AnalyticsData {
    period_days: number;
    allowed_periods: number[];
    all_time_lowest_price: number | null;
    period_lowest_price: number | null;
    period_highest_price: number | null;
    period_average_price: number | null;
    savings_percentage: number | null;
    history: PricePoint[];
}

const props = defineProps<{
    product: ProductDetail;
    analytics: AnalyticsData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Produtos Monitorados',
                href: productsIndex(),
            },
            {
                title: 'Detalhes e Histórico',
                href: '#',
            },
        ],
    },
});

const page = usePage();
const categories = computed<string[]>(() => {
    return (
        page.props.monitoringSummary?.categories ?? [
            'Hardware',
            'Periféricos',
            'Smartphones',
            'Eletrodomésticos',
            'Games',
            'Informática',
            'Áudio e Vídeo',
            'Outros',
        ]
    );
});

const isCheckingNow = ref(false);
const isEditDialogOpen = ref(false);

const editForm = useForm({
    target_price: props.product.target_price.toString(),
    nickname: props.product.nickname ?? '',
    category: props.product.category,
    notify_on_stock: props.product.notify_on_stock,
});

const openEditModal = (): void => {
    editForm.target_price = props.product.target_price.toString();
    editForm.nickname = props.product.nickname ?? '';
    editForm.category = props.product.category;
    editForm.notify_on_stock = props.product.notify_on_stock;
    editForm.clearErrors();
    isEditDialogOpen.value = true;
};

const submitEdit = (): void => {
    editForm.put(updateRoute.url({ product: props.product.id }), {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
        },
    });
};

const requestCheckNow = (): void => {
    isCheckingNow.value = true;
    router.post(
        checkNowRoute.url({ product: props.product.id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isCheckingNow.value = false;
            },
        },
    );
};

const changePeriod = (days: number): void => {
    router.get(
        showRoute.url(
            { product: props.product.id },
            { query: { period: days } },
        ),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const isTargetHit = computed<boolean>(() => {
    return (
        props.product.in_stock &&
        props.product.current_price !== null &&
        props.product.current_price <= props.product.target_price
    );
});

const pixSavingsAmount = computed<number | null>(() => {
    if (
        props.product.current_price !== null &&
        props.product.installment_price !== null &&
        props.product.installment_price > props.product.current_price
    ) {
        return props.product.installment_price - props.product.current_price;
    }
    return null;
});

const lowestRecordedDate = computed<string>(() => {
    if (props.analytics.history.length === 0) {
        return props.product.last_checked_at
            ? formatDateOnly(props.product.last_checked_at)
            : 'Sem registro';
    }

    let minPt = props.analytics.history[0];
    for (const pt of props.analytics.history) {
        if (pt.price <= minPt.price) {
            minPt = pt;
        }
    }
    return formatDateOnly(minPt.recorded_at);
});

const formatCurrency = (value: number | null): string => {
    if (value === null || Number.isNaN(value)) {
        return 'Indisponível';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

const formatDateOnly = (dateStr: string | null): string => {
    if (!dateStr) {
        return 'Nunca verificado';
    }

    try {
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'short',
        }).format(new Date(dateStr));
    } catch {
        return dateStr;
    }
};

const formatRelativeCheck = (dateStr: string | null): string => {
    if (!dateStr) {
        return 'pendente de checagem';
    }

    try {
        const diffMs = Date.now() - new Date(dateStr).getTime();
        const diffMin = Math.max(1, Math.floor(diffMs / 60000));
        if (diffMin < 60) {
            return `há ${diffMin} minutos via Web Crawler`;
        }
        const diffHours = Math.floor(diffMin / 60);
        if (diffHours < 24) {
            return `há ${diffHours} ${diffHours === 1 ? 'hora' : 'horas'} via Web Crawler`;
        }
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(new Date(dateStr));
    } catch {
        return dateStr;
    }
};

const getStoreBadge = (domain: string): { label: string; classes: string } => {
    const clean = domain.toLowerCase();
    if (clean.includes('kabum')) {
        return {
            label: 'KaBuM!',
            classes: 'bg-[#FF6500]/15 text-[#FF8533] border-[#FF6500]/30',
        };
    }
    if (clean.includes('amazon')) {
        return {
            label: 'Amazon',
            classes: 'bg-[#FF9900]/15 text-[#FFB03B] border-[#FF9900]/30',
        };
    }
    if (clean.includes('mercadolivre')) {
        return {
            label: 'Mercado Livre',
            classes: 'bg-[#FFE600]/15 text-[#E5CE00] border-[#FFE600]/30',
        };
    }
    if (clean.includes('magalu') || clean.includes('magazineluiza')) {
        return {
            label: 'Magalu',
            classes: 'bg-[#0086FF]/15 text-[#33A1FF] border-[#0086FF]/30',
        };
    }
    if (clean.includes('pichau')) {
        return {
            label: 'Pichau',
            classes: 'bg-[#E51B24]/15 text-[#FF5A62] border-[#E51B24]/30',
        };
    }
    return {
        label: domain,
        classes:
            'bg-surface-elevated text-text-secondary border-surface-border',
    };
};

const getCategoryIcon = (category: string) => {
    const clean = category.toLowerCase();
    if (clean.includes('hardware')) return Cpu;
    if (clean.includes('perif')) return Monitor;
    if (clean.includes('smart')) return Smartphone;
    if (clean.includes('game')) return Gamepad2;
    if (clean.includes('áudio') || clean.includes('audio')) return Headphones;
    return Package;
};
</script>

<template>
    <Head
        :title="`${product.nickname || product.title || 'Detalhes do Produto'} — Evolução de Preço`"
    />

    <div>
        <!-- Breadcrumb Contextual / Voltar -->
        <div class="mb-6 flex items-center gap-3">
            <Link
                :href="productsIndex()"
                class="rounded-lg border border-surface-border bg-surface p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-text-primary"
                title="Voltar ao Painel"
            >
                <ArrowLeft class="size-5" />
            </Link>
            <div>
                <div
                    class="flex items-center gap-1.5 text-body-sm text-text-muted"
                >
                    <Link
                        :href="productsIndex()"
                        class="transition-colors hover:text-text-primary"
                    >
                        Monitor de Preços
                    </Link>
                    <span>/</span>
                    <span class="text-text-secondary">{{
                        product.category
                    }}</span>
                </div>
                <h1
                    class="font-title text-title-md leading-tight font-bold text-text-primary"
                >
                    Evolução de Preço & Radar
                </h1>
            </div>
        </div>

        <!-- Banner de Erro de Verificação Manual -->
        <div
            v-if="$page.props.errors?.manual_check"
            class="mb-6 rounded-lg border border-amber/40 bg-amber-glow px-4 py-3 text-body-sm font-medium text-amber"
        >
            {{ $page.props.errors.manual_check }}
        </div>

        <!-- Header do Produto em Destaque -->
        <section
            class="mb-8 flex flex-col items-start justify-between gap-6 rounded-lg border border-surface-border bg-surface p-6 shadow-sm md:flex-row md:items-center"
        >
            <!-- Detalhes do Produto & Loja -->
            <div class="flex items-start gap-5 sm:items-center">
                <div
                    class="relative flex size-20 shrink-0 items-center justify-center rounded-xl border border-surface-border bg-surface-elevated p-3 text-primary"
                >
                    <img
                        v-if="product.image_url"
                        :src="product.image_url"
                        :alt="product.nickname || product.title || 'Produto'"
                        class="size-full object-contain"
                    />
                    <component
                        :is="getCategoryIcon(product.category)"
                        v-else
                        class="size-10"
                    />
                    <span
                        v-if="
                            analytics.savings_percentage !== null &&
                            analytics.savings_percentage > 0
                        "
                        class="absolute -right-1 -bottom-1 rounded bg-primary px-1.5 py-0.5 font-mono text-[10px] font-bold text-on-primary"
                    >
                        -{{ Math.round(analytics.savings_percentage) }}%
                    </span>
                </div>

                <div>
                    <div class="mb-1.5 flex flex-wrap items-center gap-2.5">
                        <span
                            :class="[
                                'rounded-full border px-2.5 py-0.5 font-label text-[11px] font-bold tracking-wide',
                                getStoreBadge(product.domain).classes,
                            ]"
                        >
                            {{ getStoreBadge(product.domain).label }}
                        </span>
                        <span class="font-label text-label-sm text-text-muted">
                            {{ product.category }}
                        </span>
                        <span
                            v-if="isTargetHit"
                            class="inline-flex items-center gap-1.5 rounded-full border border-primary/25 bg-primary-glow px-2.5 py-0.5 font-label text-label-sm font-semibold text-primary"
                        >
                            <span
                                class="size-1.5 animate-pulse rounded-full bg-primary"
                            />
                            Meta Atingida!
                        </span>
                        <span
                            v-else-if="!product.in_stock"
                            class="inline-flex items-center gap-1.5 rounded-full border border-coral/30 bg-coral-glow px-2.5 py-0.5 font-label text-label-sm font-semibold text-coral"
                        >
                            <span class="size-1.5 rounded-full bg-coral" />
                            Esgotado
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 rounded-full border border-surface-border bg-surface-elevated px-2.5 py-0.5 font-label text-label-sm font-medium text-text-secondary"
                        >
                            <span
                                class="size-1.5 rounded-full bg-text-secondary"
                            />
                            {{ product.status_label }}
                        </span>
                    </div>

                    <h2
                        class="font-headline text-headline-md font-bold text-text-primary"
                    >
                        {{
                            product.title ||
                            product.nickname ||
                            product.normalized_url
                        }}
                    </h2>

                    <p
                        class="mt-1 flex flex-wrap items-center gap-2 text-body-sm text-text-secondary"
                    >
                        <span v-if="product.nickname">
                            Apelido configurado:
                            <strong class="font-semibold text-text-primary">{{
                                product.nickname
                            }}</strong>
                        </span>
                        <span v-if="product.nickname" class="text-text-muted">
                            •
                        </span>
                        <span class="text-text-muted">
                            Última checagem:
                            {{ formatRelativeCheck(product.last_checked_at) }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Botões de Ação do Header -->
            <div
                class="flex w-full flex-wrap items-center justify-end gap-3 md:w-auto"
            >
                <button
                    type="button"
                    :disabled="isCheckingNow || product.status === 'paused'"
                    class="inline-flex items-center gap-2 rounded-lg border border-surface-border bg-surface-elevated px-3.5 py-2.5 text-label-md font-medium text-text-primary transition-colors hover:bg-surface-elevated/80 disabled:opacity-40"
                    @click="requestCheckNow"
                >
                    <Spinner v-if="isCheckingNow" class="size-4" />
                    <RefreshCw v-else class="size-4" />
                    <span>Verificar agora</span>
                </button>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-amber/30 bg-surface-elevated px-3.5 py-2.5 text-label-md font-medium text-amber transition-colors hover:bg-surface-elevated/80"
                    @click="openEditModal"
                >
                    <Pencil class="size-4" />
                    <span>Editar Meta</span>
                </button>

                <a
                    :href="product.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-label-md font-bold text-on-primary shadow-sm transition-all hover:bg-[#2bbd87]"
                >
                    <span>Ir para a Loja</span>
                    <ExternalLink class="size-4" />
                </a>
            </div>
        </section>

        <!-- 3 Cartões de Métricas de Destaque (Acima do Gráfico) -->
        <section class="mb-8 grid grid-cols-1 gap-5 md:grid-cols-3">
            <!-- Métrica 1: Preço Atual (PIX) vs A Prazo -->
            <div
                class="flex flex-col justify-between rounded-lg border border-surface-border bg-surface p-5"
            >
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <span
                            class="text-body-sm font-medium text-text-secondary"
                        >
                            Preço Atual à Vista (PIX)
                        </span>
                        <span
                            class="rounded border border-primary/20 bg-primary-glow px-2 py-0.5 font-mono text-[10px] font-bold text-primary"
                        >
                            COTAÇÃO ATUAL
                        </span>
                    </div>
                    <div
                        class="mt-1 font-price text-price-xl font-bold text-primary tabular-nums"
                    >
                        {{ formatCurrency(product.current_price) }}
                    </div>
                    <div
                        class="mt-2 flex items-center justify-between border-t border-surface-border/60 pt-2 text-body-sm"
                    >
                        <span class="text-text-muted">
                            Preço parcelado (a prazo):
                        </span>
                        <span
                            class="font-price font-semibold text-text-secondary tabular-nums"
                        >
                            {{
                                product.installment_price !== null
                                    ? formatCurrency(product.installment_price)
                                    : 'Não informado'
                            }}
                        </span>
                    </div>
                </div>
                <div class="mt-2 text-[11px] text-text-muted">
                    <template v-if="pixSavingsAmount !== null">
                        Economia de
                        <span class="font-mono font-medium text-primary">{{
                            formatCurrency(pixSavingsAmount)
                        }}</span>
                        pagando no PIX
                    </template>
                    <template v-else>
                        Monitoramento ativo em {{ product.domain }}
                    </template>
                </div>
            </div>

            <!-- Métrica 2: Menor Preço Histórico (Destaque Emerald) -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-lg border border-primary/40 bg-surface bg-gradient-to-br from-surface to-primary-glow/20 p-5"
            >
                <div class="relative z-10">
                    <div class="mb-1 flex items-center justify-between">
                        <span
                            class="flex items-center gap-1 text-body-sm font-semibold text-primary"
                        >
                            <History class="size-4" />
                            Menor Preço Histórico
                        </span>
                        <span
                            class="rounded bg-primary px-2 py-0.5 font-mono text-[10px] font-bold text-on-primary"
                        >
                            RECORDE
                        </span>
                    </div>
                    <div
                        class="mt-1 font-price text-price-xl font-bold text-primary tabular-nums"
                    >
                        {{ formatCurrency(analytics.all_time_lowest_price) }}
                    </div>
                    <div
                        class="mt-2 flex items-center justify-between border-t border-primary/20 pt-2 text-body-sm"
                    >
                        <span class="text-text-muted">Registrado em:</span>
                        <span
                            class="font-price font-medium text-text-primary tabular-nums"
                        >
                            {{ lowestRecordedDate }}
                        </span>
                    </div>
                </div>
                <div
                    class="relative z-10 mt-2 text-[11px] font-medium text-primary/80"
                >
                    Menor cotação detectada desde o início do rastreamento
                </div>
                <div
                    class="pointer-events-none absolute -right-3 -bottom-3 size-20 rounded-full bg-primary/10 blur-xl"
                />
            </div>

            <!-- Métrica 3: Economia Atual vs Média -->
            <div
                class="flex flex-col justify-between rounded-lg border border-surface-border bg-surface p-5"
            >
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <span
                            class="text-body-sm font-medium text-text-secondary"
                        >
                            Economia Atual
                        </span>
                        <span
                            :class="[
                                'rounded border px-2 py-0.5 font-mono text-[10px] font-bold',
                                isTargetHit
                                    ? 'border-amber/20 bg-amber-glow text-amber'
                                    : 'border-surface-border bg-surface-elevated text-text-secondary',
                            ]"
                        >
                            {{
                                isTargetHit
                                    ? 'PREÇO ALVO OK'
                                    : 'EM MONITORAMENTO'
                            }}
                        </span>
                    </div>
                    <div
                        class="mt-1 font-price text-price-xl font-bold text-primary tabular-nums"
                    >
                        <template v-if="analytics.savings_percentage !== null">
                            {{
                                analytics.savings_percentage > 0
                                    ? `-${analytics.savings_percentage}%`
                                    : `${Math.abs(analytics.savings_percentage)}%`
                            }}
                            <span
                                class="text-price-md font-normal text-text-muted"
                            >
                                {{
                                    analytics.savings_percentage >= 0
                                        ? 'abaixo da média'
                                        : 'acima da média'
                                }}
                            </span>
                        </template>
                        <template v-else>
                            0%
                            <span
                                class="text-price-md font-normal text-text-muted"
                            >
                                na média
                            </span>
                        </template>
                    </div>
                    <div
                        class="mt-2 flex items-center justify-between border-t border-surface-border/60 pt-2 text-body-sm"
                    >
                        <span class="text-text-muted">
                            Preço médio ({{ analytics.period_days }}d):
                        </span>
                        <span
                            class="font-price font-semibold text-text-secondary tabular-nums"
                        >
                            {{ formatCurrency(analytics.period_average_price) }}
                        </span>
                    </div>
                </div>
                <div
                    class="mt-2 flex items-center gap-1 font-mono text-[11px] font-semibold text-amber"
                >
                    <CheckCircle2 v-if="isTargetHit" class="size-3.5" />
                    <span v-if="isTargetHit && product.current_price !== null">
                        {{
                            formatCurrency(
                                product.target_price - product.current_price,
                            )
                        }}
                        abaixo da sua meta de
                        {{ formatCurrency(product.target_price) }}
                    </span>
                    <span v-else class="text-text-muted">
                        Sua meta configurada:
                        {{ formatCurrency(product.target_price) }}
                    </span>
                </div>
            </div>
        </section>

        <!-- Gráfico Interativo de Spline de Área (Histórico & Metas) -->
        <section
            class="rounded-lg border border-surface-border bg-surface p-6 shadow-sm"
        >
            <!-- Header do Gráfico: Título, Legendas e Segmented Period Pills -->
            <div
                class="flex flex-col items-start justify-between gap-4 border-b border-surface-border pb-6 lg:flex-row lg:items-center"
            >
                <div>
                    <h3
                        class="flex items-center gap-2 font-headline text-headline-md font-bold text-text-primary"
                    >
                        Evolução do Preço no Tempo
                        <span
                            title="Gráfico comparativo de preço à vista PIX vs Preço parcelado vs Preço alvo"
                        >
                            <Info class="size-4 text-text-muted" />
                        </span>
                    </h3>
                    <p class="mt-0.5 text-body-sm text-text-secondary">
                        Histórico consolidado com identificação de quedas
                        repentinas e promoções relâmpago.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <!-- Legendas de Cores -->
                    <div class="flex items-center gap-3.5 text-body-sm">
                        <span
                            class="flex items-center gap-1.5 text-text-secondary"
                        >
                            <span class="size-3 rounded-xs bg-primary" />
                            À Vista (PIX)
                        </span>
                        <span
                            class="flex items-center gap-1.5 text-text-secondary"
                        >
                            <span
                                class="h-0.5 w-3.5 border-t-2 border-dashed border-indigo"
                            />
                            A Prazo
                        </span>
                        <span
                            class="flex items-center gap-1.5 text-text-secondary"
                        >
                            <span
                                class="h-0.5 w-3.5 border-t-2 border-dotted border-amber"
                            />
                            Preço Alvo ({{
                                formatCurrency(product.target_price)
                            }})
                        </span>
                    </div>

                    <!-- Segmented Period Selector Pills -->
                    <div
                        class="flex items-center gap-1 rounded-lg border border-surface-border bg-surface-elevated p-1"
                    >
                        <button
                            v-for="period in analytics.allowed_periods"
                            :key="period"
                            type="button"
                            :class="[
                                'rounded-md px-3 py-1 font-label text-label-sm transition-colors',
                                analytics.period_days === period
                                    ? 'bg-primary font-bold text-on-primary shadow-xs'
                                    : 'font-medium text-text-secondary hover:text-text-primary',
                            ]"
                            @click="changePeriod(period)"
                        >
                            {{ period }} dias
                        </button>
                    </div>
                </div>
            </div>

            <!-- Componente do Gráfico -->
            <PriceHistoryChart
                :history="analytics.history"
                :target-price="product.target_price"
                :period-days="analytics.period_days"
            />
        </section>
    </div>

    <!-- Modal de Edição de Meta -->
    <Dialog v-model:open="isEditDialogOpen">
        <DialogContent
            class="border-surface-border bg-surface-elevated text-text-primary sm:max-w-md"
        >
            <DialogHeader>
                <DialogTitle
                    class="font-headline text-headline-md text-text-primary"
                >
                    Editar Meta do Produto
                </DialogTitle>
                <DialogDescription class="text-body-sm text-text-secondary">
                    Ajuste o preço alvo e preferências de alerta sem perder o
                    histórico coletado.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4 pt-2" @submit.prevent="submitEdit">
                <div>
                    <label
                        for="show-edit-target-price"
                        class="mb-1.5 block font-label text-label-md text-text-secondary"
                    >
                        Preço Alvo (R$)
                    </label>
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 font-price text-sm font-semibold text-text-muted"
                        >
                            R$
                        </span>
                        <input
                            id="show-edit-target-price"
                            v-model="editForm.target_price"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            class="w-full rounded-lg border border-amber/50 bg-surface py-2.5 pr-4 pl-10 font-price text-price-md font-semibold text-text-primary tabular-nums focus:border-amber focus:ring-1 focus:ring-amber focus:outline-none"
                        />
                    </div>
                    <InputError
                        :message="editForm.errors.target_price"
                        class="mt-1"
                    />
                </div>

                <div>
                    <label
                        for="show-edit-nickname"
                        class="mb-1.5 block font-label text-label-md text-text-secondary"
                    >
                        Apelido (Opcional)
                    </label>
                    <input
                        id="show-edit-nickname"
                        v-model="editForm.nickname"
                        type="text"
                        maxlength="120"
                        class="w-full rounded-lg border border-surface-border bg-surface px-3.5 py-2.5 font-body text-body-sm text-text-primary focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                    />
                    <InputError
                        :message="editForm.errors.nickname"
                        class="mt-1"
                    />
                </div>

                <div>
                    <label
                        for="show-edit-category"
                        class="mb-1.5 block font-label text-label-md text-text-secondary"
                    >
                        Categoria
                    </label>
                    <select
                        id="show-edit-category"
                        v-model="editForm.category"
                        required
                        class="w-full rounded-lg border border-surface-border bg-surface px-3.5 py-2.5 font-label text-label-md text-text-primary focus:border-primary focus:outline-none"
                    >
                        <option
                            v-for="cat in categories"
                            :key="cat"
                            :value="cat"
                        >
                            {{ cat }}
                        </option>
                    </select>
                </div>

                <div
                    class="flex items-center justify-between rounded-lg border border-surface-border bg-surface p-3.5"
                >
                    <span class="font-label text-label-md text-text-primary">
                        Avisar também quando voltar ao estoque
                    </span>
                    <label
                        class="relative inline-flex cursor-pointer items-center"
                    >
                        <input
                            v-model="editForm.notify_on_stock"
                            type="checkbox"
                            class="peer sr-only"
                        />
                        <div
                            class="h-6 w-11 rounded-full border border-surface-border bg-canvas peer-checked:bg-primary after:absolute after:top-[2px] after:left-[2px] after:size-5 after:rounded-full after:bg-text-primary after:transition-all after:content-[''] peer-checked:after:translate-x-full"
                        />
                    </label>
                </div>

                <DialogFooter class="gap-2 pt-2">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 font-label text-label-md text-text-secondary transition-colors hover:bg-surface hover:text-text-primary"
                        @click="isEditDialogOpen = false"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="editForm.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 font-label text-label-md font-bold text-on-primary transition-all hover:bg-[#2bbd87]"
                    >
                        <Spinner
                            v-if="editForm.processing"
                            class="size-4 text-on-primary"
                        />
                        <span>Salvar alterações</span>
                    </button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
