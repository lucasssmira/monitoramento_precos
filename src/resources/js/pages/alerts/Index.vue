<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Bell,
    BellOff,
    Check,
    CheckCheck,
    Package,
    TrendingDown,
} from '@lucide/vue';
import { ref } from 'vue';
import { Spinner } from '@/components/ui/spinner';
import {
    index as alertsIndex,
    markAllAsRead as markAllAsReadRoute,
    markAsRead as markAsReadRoute,
} from '@/routes/alerts';
import {
    index as productsIndex,
    show as productShowRoute,
} from '@/routes/products';

interface AlertProduct {
    id: number;
    title: string | null;
    nickname: string | null;
    url: string;
    domain: string;
    category: string;
    image_url: string | null;
}

interface AlertItem {
    id: number;
    type: 'price_drop' | 'back_in_stock';
    type_label: string;
    triggered_price: number;
    installment_price: number | null;
    previous_price: number | null;
    target_price: number;
    read_at: string | null;
    is_read: boolean;
    created_at: string | null;
    product: AlertProduct;
}

defineProps<{
    alerts: AlertItem[];
    unreadCount: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Central de Alertas',
                href: alertsIndex(),
            },
        ],
    },
});

const markingId = ref<number | null>(null);
const isMarkingAll = ref(false);

const markAsRead = (alert: AlertItem): void => {
    markingId.value = alert.id;
    router.patch(
        markAsReadRoute.url({ alert: alert.id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                markingId.value = null;
            },
        },
    );
};

const markAllAsRead = (): void => {
    isMarkingAll.value = true;
    router.post(
        markAllAsReadRoute.url(),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isMarkingAll.value = false;
            },
        },
    );
};

const formatCurrency = (value: number | null): string => {
    if (value === null || Number.isNaN(value)) {
        return 'Indisponível';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

const formatRelativeTime = (dateStr: string | null): string => {
    if (!dateStr) {
        return 'agora';
    }

    try {
        const diffMs = Date.now() - new Date(dateStr).getTime();
        const diffMin = Math.max(1, Math.floor(diffMs / 60000));
        if (diffMin < 60) {
            return `há ${diffMin} min`;
        }
        const diffHours = Math.floor(diffMin / 60);
        if (diffHours < 24) {
            return `há ${diffHours} ${diffHours === 1 ? 'hora' : 'horas'}`;
        }
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(new Date(dateStr));
    } catch {
        return dateStr;
    }
};

const getStoreName = (domain: string): string => {
    const clean = domain.toLowerCase();
    if (clean.includes('kabum')) return 'KaBuM!';
    if (clean.includes('amazon')) return 'Amazon';
    if (clean.includes('mercadolivre')) return 'Mercado Livre';
    if (clean.includes('magalu') || clean.includes('magazineluiza'))
        return 'Magalu';
    if (clean.includes('pichau')) return 'Pichau';
    return domain;
};

const getTargetDiffText = (alert: AlertItem): string => {
    const diff = alert.target_price - alert.triggered_price;
    if (diff > 0) {
        return `-${formatCurrency(diff)} abaixo da meta`;
    }
    if (diff === 0) {
        return 'Exatamente no preço alvo';
    }
    return `Alvo: ${formatCurrency(alert.target_price)}`;
};
</script>

<template>
    <Head title="Central de Alertas — Monitor de Preços" />

    <div>
        <!-- Topo da Central de Alertas -->
        <div
            class="mb-8 flex flex-col justify-between gap-4 border-b border-surface-border pb-6 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-3.5">
                <Link
                    :href="productsIndex()"
                    class="rounded-lg border border-surface-border bg-surface p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-text-primary"
                    title="Voltar ao Painel"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div
                    class="relative flex size-11 items-center justify-center rounded-xl border border-amber/30 bg-amber-glow text-amber"
                >
                    <Bell class="size-6" />
                    <span
                        v-if="unreadCount > 0"
                        class="absolute -top-0.5 -right-0.5 size-2.5 animate-ping rounded-full bg-amber"
                    />
                </div>
                <div>
                    <h1
                        class="font-headline text-headline-md leading-tight font-bold text-text-primary"
                    >
                        Central de Alertas
                    </h1>
                    <p class="mt-0.5 text-body-sm text-text-secondary">
                        Histórico de oportunidades de preço atingido e retorno
                        ao estoque.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span
                    v-if="unreadCount > 0"
                    class="inline-flex items-center gap-1.5 rounded-full border border-amber/30 bg-amber-glow px-3 py-1 font-label text-label-sm font-semibold text-amber"
                >
                    <span
                        class="size-1.5 animate-pulse rounded-full bg-amber"
                    />
                    {{ unreadCount }} não lido(s)
                </span>

                <button
                    v-if="unreadCount > 0"
                    type="button"
                    :disabled="isMarkingAll"
                    class="inline-flex items-center gap-2 rounded-lg border border-surface-border bg-surface-elevated px-3.5 py-2 font-label text-label-md font-medium text-text-primary transition-colors hover:border-primary/40 hover:text-primary disabled:opacity-50"
                    @click="markAllAsRead"
                >
                    <Spinner v-if="isMarkingAll" class="size-4" />
                    <CheckCheck v-else class="size-4" />
                    <span>Marcar todos como lidos</span>
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="alerts.length === 0"
            class="flex flex-col items-center justify-center rounded-xl border border-surface-border bg-surface py-16 text-center"
        >
            <div
                class="mb-4 flex size-14 items-center justify-center rounded-xl border border-surface-border bg-surface-elevated text-text-muted"
            >
                <BellOff class="size-7" />
            </div>
            <p class="font-title text-title-lg font-semibold text-text-primary">
                Nenhum alerta disparado até o momento
            </p>
            <p class="mt-1 max-w-md px-4 text-body-sm text-text-secondary">
                Assim que os coletores identificarem uma oferta atingindo seu
                preço alvo ou o retorno de um item ao estoque, as notificações
                aparecerão aqui.
            </p>
        </div>

        <!-- Grid de Alertas -->
        <div
            v-else
            class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3"
        >
            <div
                v-for="alert in alerts"
                :key="alert.id"
                :class="[
                    'group relative flex flex-col justify-between overflow-hidden rounded-lg border bg-surface p-5 transition-colors',
                    alert.type === 'price_drop'
                        ? !alert.is_read
                            ? 'border-primary/40 hover:border-primary'
                            : 'border-surface-border hover:border-primary/40'
                        : !alert.is_read
                          ? 'border-amber/40 hover:border-amber'
                          : 'border-surface-border hover:border-amber/40',
                ]"
            >
                <div>
                    <!-- Topo do Card -->
                    <div class="mb-2.5 flex items-start justify-between gap-2">
                        <span
                            v-if="alert.type === 'price_drop'"
                            class="inline-flex items-center gap-1 rounded bg-primary-glow px-2 py-0.5 font-label text-[11px] font-bold text-primary"
                        >
                            <TrendingDown class="size-3" />
                            Meta de Preço Atingida!
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 rounded bg-amber-glow px-2 py-0.5 font-label text-[11px] font-bold text-amber"
                        >
                            <Package class="size-3" />
                            Voltou ao Estoque!
                        </span>

                        <div class="flex items-center gap-2">
                            <span class="font-mono text-[11px] text-text-muted">
                                {{ formatRelativeTime(alert.created_at) }}
                            </span>
                            <button
                                v-if="!alert.is_read"
                                type="button"
                                :disabled="markingId === alert.id"
                                title="Marcar como lido"
                                class="rounded border border-surface-border bg-surface-elevated p-1 text-text-secondary transition-colors hover:border-primary/40 hover:text-primary"
                                @click="markAsRead(alert)"
                            >
                                <Spinner
                                    v-if="markingId === alert.id"
                                    class="size-3.5"
                                />
                                <Check v-else class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Título do Produto -->
                    <Link
                        :href="
                            productShowRoute.url({
                                product: alert.product.id,
                            })
                        "
                        class="line-clamp-2 block font-title text-title-md leading-snug font-semibold text-text-primary transition-colors hover:text-primary"
                    >
                        {{
                            alert.product.nickname ||
                            alert.product.title ||
                            'Produto Monitorado'
                        }}
                    </Link>
                    <span class="mt-0.5 block text-[11px] text-text-muted">
                        {{ getStoreName(alert.product.domain) }} •
                        {{ alert.product.category }}
                    </span>

                    <!-- Bloco de Valores -->
                    <div
                        v-if="alert.type === 'price_drop'"
                        class="mt-3.5 flex items-baseline justify-between rounded-md border border-surface-border bg-canvas/70 p-3"
                    >
                        <div>
                            <span class="block text-[11px] text-text-muted">
                                Preço anterior:
                            </span>
                            <span
                                class="font-price text-sm text-text-muted tabular-nums line-through"
                            >
                                {{
                                    alert.previous_price
                                        ? formatCurrency(alert.previous_price)
                                        : formatCurrency(alert.target_price)
                                }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span
                                class="block text-[11px] font-medium text-primary"
                            >
                                Novo Preço à Vista (PIX):
                            </span>
                            <span
                                class="font-price text-price-lg font-bold text-primary tabular-nums"
                            >
                                {{ formatCurrency(alert.triggered_price) }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-else
                        class="mt-3.5 flex items-baseline justify-between rounded-md border border-surface-border bg-canvas/70 p-3"
                    >
                        <div>
                            <span class="block text-[11px] text-text-muted">
                                Status de Estoque:
                            </span>
                            <span
                                class="mt-0.5 flex items-center gap-1 text-[12px] font-semibold text-amber"
                            >
                                <span class="size-1.5 rounded-full bg-amber" />
                                Disponível em estoque
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="block text-[11px] text-text-secondary">
                                Preço PIX Atual:
                            </span>
                            <span
                                class="font-price text-price-md font-bold text-text-primary tabular-nums"
                            >
                                {{ formatCurrency(alert.triggered_price) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Rodapé do Card -->
                <div
                    class="mt-4 flex items-center justify-between border-t border-surface-border pt-3"
                >
                    <span
                        :class="[
                            'font-mono text-[11px]',
                            alert.type === 'price_drop'
                                ? 'font-medium text-amber'
                                : 'text-text-muted',
                        ]"
                    >
                        {{ getTargetDiffText(alert) }}
                    </span>
                    <a
                        :href="alert.product.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        :class="[
                            'inline-flex items-center gap-1 font-label text-label-sm font-bold hover:underline',
                            alert.type === 'price_drop'
                                ? 'text-primary'
                                : 'text-amber',
                        ]"
                    >
                        <span>{{
                            alert.type === 'price_drop'
                                ? 'Comprar agora'
                                : 'Ir para a Loja'
                        }}</span>
                        <ArrowRight class="size-3.5" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
