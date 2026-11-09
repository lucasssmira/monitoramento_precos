<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bell,
    BellOff,
    Check,
    Package,
    TrendingDown,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import {
    index as alertsIndex,
    markAllAsRead as markAllAsReadRoute,
    markAsRead as markAsReadRoute,
} from '@/routes/alerts';
import { show as productShowRoute } from '@/routes/products';
import type { SharedAlertItem } from '@/types';

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const page = usePage();

const alerts = computed<SharedAlertItem[]>(() => {
    return page.props.monitoringSummary?.recentAlerts ?? [];
});

const unreadCount = computed<number>(() => {
    return page.props.monitoringSummary?.unreadAlertsCount ?? 0;
});

const markingId = ref<number | null>(null);
const isMarkingAll = ref(false);

const markAsRead = (alert: SharedAlertItem): void => {
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
        const diffDays = Math.floor(diffHours / 24);
        return `há ${diffDays} ${diffDays === 1 ? 'dia' : 'dias'}`;
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

const getTargetDiffText = (alert: SharedAlertItem): string => {
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
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent
            side="right"
            class="flex w-full flex-col border-l border-surface-border bg-surface-elevated p-6 text-text-primary shadow-2xl sm:max-w-[420px] [&>button]:hidden"
        >
            <!-- Topo da Central de Alertas -->
            <div
                class="mb-5 flex items-center justify-between border-b border-surface-border pb-4"
            >
                <div class="flex items-center gap-2.5">
                    <div class="relative">
                        <Bell class="size-6 text-amber" />
                        <span
                            v-if="unreadCount > 0"
                            class="absolute -top-0.5 -right-0.5 size-2 animate-ping rounded-full bg-amber ring-2 ring-surface-elevated"
                        />
                    </div>
                    <div>
                        <SheetTitle
                            class="font-headline text-headline-md leading-tight font-bold text-text-primary"
                        >
                            Central de Alertas
                        </SheetTitle>
                        <SheetDescription class="text-[12px] text-text-muted">
                            {{
                                unreadCount > 0
                                    ? `${unreadCount} notificação(ões) não lida(s)`
                                    : `${alerts.length} notificação(ões) registrada(s)`
                            }}
                        </SheetDescription>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        :disabled="isMarkingAll"
                        class="pr-2 text-xs text-text-muted transition-colors hover:text-primary disabled:opacity-50"
                        @click="markAllAsRead"
                    >
                        <Spinner v-if="isMarkingAll" class="size-3.5" />
                        <span v-else>Limpar</span>
                    </button>
                    <button
                        type="button"
                        class="rounded-lg p-1 text-text-muted transition-colors hover:bg-surface hover:text-text-primary"
                        title="Fechar"
                        @click="emit('update:open', false)"
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </div>

            <!-- Feed Limpo de Notificações Disparadas -->
            <div class="flex-1 space-y-4 overflow-y-auto pr-1">
                <div
                    v-if="alerts.length === 0"
                    class="flex flex-col items-center justify-center rounded-lg border border-surface-border bg-surface py-12 text-center"
                >
                    <BellOff class="mb-3 size-8 text-text-muted" />
                    <p
                        class="font-title text-sm font-semibold text-text-primary"
                    >
                        Nenhum alerta disparado
                    </p>
                    <p class="mt-1 max-w-xs px-4 text-xs text-text-muted">
                        Quando um produto atingir seu preço alvo ou voltar ao
                        estoque, ele aparecerá aqui.
                    </p>
                </div>

                <!-- Card Alerta -->
                <div
                    v-for="alert in alerts"
                    :key="alert.id"
                    :class="[
                        'group relative overflow-hidden rounded-lg border bg-surface p-4 transition-colors',
                        alert.type === 'price_drop'
                            ? !alert.is_read
                                ? 'border-primary/40 hover:border-primary'
                                : 'border-surface-border hover:border-primary/40'
                            : !alert.is_read
                              ? 'border-amber/40 hover:border-amber'
                              : 'border-surface-border hover:border-amber/40',
                    ]"
                >
                    <div class="mb-2 flex items-start justify-between gap-2">
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
                                class="rounded p-0.5 text-text-muted transition-colors hover:bg-surface-elevated hover:text-primary"
                                @click="markAsRead(alert)"
                            >
                                <Check class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    <Link
                        :href="
                            productShowRoute.url({
                                product: alert.product.id,
                            })
                        "
                        class="line-clamp-2 block font-title text-title-md leading-snug font-semibold text-text-primary transition-colors hover:text-primary"
                        @click="emit('update:open', false)"
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
                        class="mt-3 flex items-baseline justify-between rounded-md border border-surface-border bg-canvas/70 p-2.5"
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
                        class="mt-3 flex items-baseline justify-between rounded-md border border-surface-border bg-canvas/70 p-2.5"
                    >
                        <div>
                            <span class="block text-[11px] text-text-muted">
                                Status de Estoque:
                            </span>
                            <span
                                class="mt-0.5 flex items-center gap-1 text-[12px] font-semibold text-amber"
                            >
                                <span class="size-1.5 rounded-full bg-amber" />
                                Disponível agora
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

                    <!-- Rodapé do Card -->
                    <div
                        class="mt-3.5 flex items-center justify-between border-t border-surface-border pt-2"
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

            <!-- Link para página completa da Central de Alertas -->
            <div class="mt-4 border-t border-surface-border pt-4 text-center">
                <Link
                    :href="alertsIndex()"
                    class="inline-flex items-center gap-1.5 font-label text-label-sm font-semibold text-text-secondary transition-colors hover:text-primary"
                    @click="emit('update:open', false)"
                >
                    <span>Ver histórico completo de alertas</span>
                    <ArrowRight class="size-3.5" />
                </Link>
            </div>
        </SheetContent>
    </Sheet>
</template>
