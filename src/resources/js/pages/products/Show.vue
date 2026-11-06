<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Clock,
    ExternalLink,
    PackageSearch,
    Percent,
    RotateCcw,
    Target,
    TrendingDown,
} from '@lucide/vue';
import { ref } from 'vue';
import PriceHistoryChart, {
    type PricePoint,
} from '@/components/PriceHistoryChart.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import {
    checkNow as checkNowRoute,
    index as productsIndex,
    show as showRoute,
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

const isCheckingNow = ref(false);

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

const formatCurrency = (value: number | null): string => {
    if (value === null || Number.isNaN(value)) {
        return 'Indisponível';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) {
        return 'Nunca verificado';
    }

    try {
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(new Date(dateStr));
    } catch {
        return dateStr;
    }
};
</script>

<template>
    <Head
        :title="`${product.nickname || product.title || 'Detalhes do Produto'} - Histórico`"
    />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Barra de Navegação Superior -->
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-3">
                <Button
                    as-child
                    variant="outline"
                    size="sm"
                    class="h-8 gap-1 text-xs"
                >
                    <Link :href="productsIndex()">
                        <ArrowLeft class="size-3.5" />
                        Voltar para produtos
                    </Link>
                </Button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    class="h-8 gap-1 text-xs"
                    :disabled="isCheckingNow || product.status === 'paused'"
                    title="Verificar preços agora (mínimo de 10 minutos entre checagens)"
                    @click="requestCheckNow"
                >
                    <Spinner v-if="isCheckingNow" />
                    <RotateCcw v-else class="size-3.5" />
                    Verificar agora
                </Button>

                <a
                    :href="product.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-background px-2.5 text-xs font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground"
                >
                    <ExternalLink class="size-3.5" />
                    Ver na loja
                </a>
            </div>
        </div>

        <!-- Banner de Erro de Verificação Manual -->
        <div
            v-if="$page.props.errors?.manual_check"
            class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-medium text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400"
        >
            {{ $page.props.errors.manual_check }}
        </div>

        <!-- Card de Identificação do Produto -->
        <Card>
            <CardContent class="p-6">
                <div class="flex flex-col gap-6 md:flex-row md:items-start">
                    <div
                        class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-muted sm:size-32"
                    >
                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="
                                product.nickname || product.title || 'Produto'
                            "
                            class="size-full object-contain p-2"
                        />
                        <PackageSearch
                            v-else
                            class="size-12 text-muted-foreground/40"
                        />
                    </div>

                    <div class="flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1
                                class="text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                            >
                                {{
                                    product.nickname ||
                                    product.title ||
                                    product.normalized_url
                                }}
                            </h1>
                            <Badge variant="outline" class="text-xs">
                                {{ product.category }}
                            </Badge>
                            <Badge variant="secondary" class="text-xs">
                                {{ product.domain }}
                            </Badge>
                        </div>

                        <p
                            v-if="product.nickname && product.title"
                            class="text-sm text-muted-foreground"
                        >
                            {{ product.title }}
                        </p>

                        <div
                            class="flex flex-wrap items-center gap-3 pt-2 text-xs text-muted-foreground"
                        >
                            <Badge
                                :variant="
                                    product.in_stock
                                        ? 'secondary'
                                        : 'destructive'
                                "
                                class="text-xs"
                            >
                                {{
                                    product.in_stock ? 'Em estoque' : 'Esgotado'
                                }}
                            </Badge>

                            <Badge
                                :variant="
                                    product.status === 'active'
                                        ? 'default'
                                        : product.status === 'paused'
                                          ? 'secondary'
                                          : product.status === 'unstable'
                                            ? 'destructive'
                                            : 'outline'
                                "
                                class="text-xs"
                            >
                                {{ product.status_label }}
                            </Badge>

                            <span v-if="product.notify_on_stock">
                                • Alerta de estoque ativo
                            </span>

                            <span>
                                • Última verificação:
                                {{ formatDate(product.last_checked_at) }}
                            </span>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Cards Analíticos de Preço (RF-015) -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Menor Preço Histórico -->
            <Card
                class="border-green-200/80 bg-green-50/30 dark:border-green-900/40 dark:bg-green-950/20"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between pb-2"
                >
                    <CardTitle
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Menor Preço Histórico
                    </CardTitle>
                    <TrendingDown
                        class="size-4 text-green-600 dark:text-green-400"
                    />
                </CardHeader>
                <CardContent>
                    <div
                        class="text-2xl font-bold text-green-700 dark:text-green-400"
                    >
                        {{ formatCurrency(analytics.all_time_lowest_price) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Menor valor já registrado desde o início do rastreamento
                    </p>
                </CardContent>
            </Card>

            <!-- Preço Atual -->
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between pb-2"
                >
                    <CardTitle
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Preço Atual (À vista)
                    </CardTitle>
                    <Clock class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-foreground">
                        {{ formatCurrency(product.current_price) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            product.installment_price
                                ? `A prazo: ${formatCurrency(product.installment_price)}`
                                : 'À vista / PIX'
                        }}
                    </p>
                </CardContent>
            </Card>

            <!-- Preço Médio do Período -->
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between pb-2"
                >
                    <CardTitle
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Média ({{ analytics.period_days }} dias)
                    </CardTitle>
                    <Clock class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-foreground">
                        {{ formatCurrency(analytics.period_average_price) }}
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Média aritmética no período selecionado
                    </p>
                </CardContent>
            </Card>

            <!-- Economia vs Média -->
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between pb-2"
                >
                    <CardTitle
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Economia vs Média
                    </CardTitle>
                    <Percent class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div
                        v-if="analytics.savings_percentage !== null"
                        :class="[
                            'text-2xl font-bold',
                            analytics.savings_percentage > 0
                                ? 'text-green-600 dark:text-green-400'
                                : 'text-foreground',
                        ]"
                    >
                        {{
                            analytics.savings_percentage > 0
                                ? `+${analytics.savings_percentage}%`
                                : `${analytics.savings_percentage}%`
                        }}
                    </div>
                    <div
                        v-else
                        class="text-2xl font-bold text-muted-foreground"
                    >
                        --
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{
                            analytics.savings_percentage !== null &&
                            analytics.savings_percentage > 0
                                ? 'Desconto em relação ao preço médio do período'
                                : 'Preço atual igual ou superior à média do período'
                        }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Seção do Gráfico de Evolução (RF-014) -->
        <Card>
            <CardHeader
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>Evolução de Preços</CardTitle>
                    <CardDescription>
                        Acompanhe a oscilação histórica de preços coletada para
                        este produto.
                    </CardDescription>
                </div>

                <!-- Seletor de Período (7, 30 e 90 dias - RF-014) -->
                <div
                    class="flex items-center gap-1 rounded-lg border bg-muted p-1"
                >
                    <button
                        v-for="period in analytics.allowed_periods"
                        :key="period"
                        type="button"
                        :class="[
                            'rounded-md px-3 py-1 text-xs font-medium transition-all',
                            analytics.period_days === period
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="changePeriod(period)"
                    >
                        {{ period }} dias
                    </button>
                </div>
            </CardHeader>
            <CardContent>
                <PriceHistoryChart
                    :history="analytics.history"
                    :target-price="product.target_price"
                    :period-days="analytics.period_days"
                />
            </CardContent>
        </Card>

        <!-- Informações e Configurações de Monitoramento -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base"
                    >Parâmetros de Monitoramento</CardTitle
                >
                <CardDescription>
                    Critérios e metas estabelecidas para notificações
                    automáticas deste produto.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <dl
                    class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            Preço Alvo Desejado
                        </dt>
                        <dd
                            class="flex items-center gap-1.5 font-semibold text-foreground"
                        >
                            <Target class="size-4 text-amber-500" />
                            {{ formatCurrency(product.target_price) }}
                        </dd>
                    </div>

                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            Alerta de Estoque
                        </dt>
                        <dd class="font-medium text-foreground">
                            {{
                                product.notify_on_stock
                                    ? 'Ativado (notificar quando voltar)'
                                    : 'Desativado'
                            }}
                        </dd>
                    </div>

                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            Data de Início do Rastreamento
                        </dt>
                        <dd class="font-medium text-foreground">
                            {{ formatDate(product.created_at) }}
                        </dd>
                    </div>

                    <div class="space-y-1">
                        <dt class="text-xs text-muted-foreground">
                            Última Coleta Sob Demanda
                        </dt>
                        <dd class="font-medium text-foreground">
                            {{ formatDate(product.last_manual_check_at) }}
                        </dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>
