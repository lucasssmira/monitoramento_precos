<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Bell,
    BellOff,
    Check,
    CheckCheck,
    ExternalLink,
    PackageCheck,
    PackageSearch,
    TrendingDown,
} from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
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
    index as alertsIndex,
    markAllAsRead as markAllAsReadRoute,
    markAsRead as markAsReadRoute,
} from '@/routes/alerts';
import { show as productShowRoute } from '@/routes/products';

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

const props = defineProps<{
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

const formatDate = (dateStr: string | null): string => {
    if (!dateStr) {
        return '';
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
    <Head title="Central de Alertas" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Topo da Central de Alertas -->
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <Heading
                        title="Central de Alertas"
                        description="Notificações de preços promocionais atingidos e retorno de produtos ao estoque."
                    />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Badge
                    v-if="unreadCount > 0"
                    variant="default"
                    class="h-7 text-xs"
                >
                    {{ unreadCount }} não lido(s)
                </Badge>

                <Button
                    v-if="unreadCount > 0"
                    variant="outline"
                    size="sm"
                    class="h-8 gap-1 text-xs"
                    :disabled="isMarkingAll"
                    @click="markAllAsRead"
                >
                    <Spinner v-if="isMarkingAll" />
                    <CheckCheck v-else class="size-3.5" />
                    Marcar todos como lidos
                </Button>
            </div>
        </div>

        <!-- Lista de Alertas -->
        <div class="grid gap-4">
            <!-- Empty State -->
            <Card v-if="alerts.length === 0">
                <CardContent
                    class="flex flex-col items-center justify-center py-16 text-center text-sm text-muted-foreground"
                >
                    <div
                        class="mb-4 flex size-12 items-center justify-center rounded-full bg-muted"
                    >
                        <BellOff class="size-6 text-muted-foreground/60" />
                    </div>
                    <p class="text-base font-semibold text-foreground">
                        Nenhum alerta disparado até o momento
                    </p>
                    <p class="mt-1 max-w-sm text-xs">
                        Assim que os robôs de coleta identificarem uma oferta
                        atingindo seu preço alvo ou o retorno de um produto ao
                        estoque, os alertas aparecerão nesta central.
                    </p>
                </CardContent>
            </Card>

            <!-- Cards de Alertas -->
            <Card
                v-for="alert in alerts"
                :key="alert.id"
                :class="[
                    'overflow-hidden transition-all',
                    !alert.is_read
                        ? 'border-primary/50 bg-primary/5 shadow-xs dark:bg-primary/10'
                        : 'hover:shadow-sm',
                ]"
            >
                <CardContent class="p-4 sm:p-6">
                    <div
                        class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
                    >
                        <!-- Imagem e Conteúdo do Alerta -->
                        <div class="flex items-start gap-4">
                            <div
                                class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted sm:size-20"
                            >
                                <img
                                    v-if="alert.product.image_url"
                                    :src="alert.product.image_url"
                                    :alt="
                                        alert.product.nickname ||
                                        alert.product.title ||
                                        'Produto'
                                    "
                                    class="size-full object-contain p-1"
                                />
                                <PackageSearch
                                    v-else
                                    class="size-8 text-muted-foreground/40"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge
                                        :variant="
                                            alert.type === 'price_drop'
                                                ? 'default'
                                                : 'secondary'
                                        "
                                        class="gap-1 text-xs"
                                    >
                                        <TrendingDown
                                            v-if="alert.type === 'price_drop'"
                                            class="size-3"
                                        />
                                        <PackageCheck v-else class="size-3" />
                                        {{ alert.type_label }}
                                    </Badge>

                                    <Badge
                                        v-if="!alert.is_read"
                                        variant="outline"
                                        class="border-primary text-xs font-semibold text-primary"
                                    >
                                        Novo
                                    </Badge>

                                    <Badge variant="outline" class="text-xs">
                                        {{ alert.product.category }}
                                    </Badge>

                                    <Badge variant="secondary" class="text-xs">
                                        {{ alert.product.domain }}
                                    </Badge>
                                </div>

                                <h3
                                    class="text-base font-semibold text-foreground"
                                >
                                    <Link
                                        :href="
                                            productShowRoute.url({
                                                product: alert.product.id,
                                            })
                                        "
                                        class="transition-colors hover:text-primary hover:underline"
                                    >
                                        {{
                                            alert.product.nickname ||
                                            alert.product.title ||
                                            'Produto'
                                        }}
                                    </Link>
                                </h3>

                                <div
                                    class="flex flex-wrap items-center gap-x-4 gap-y-1 pt-1 text-xs text-muted-foreground"
                                >
                                    <div>
                                        Disparado em:
                                        <span
                                            class="font-medium text-foreground"
                                        >
                                            {{ formatDate(alert.created_at) }}
                                        </span>
                                    </div>

                                    <div>
                                        Preço Alvo:
                                        <span
                                            class="font-medium text-foreground"
                                        >
                                            {{
                                                formatCurrency(
                                                    alert.target_price,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <div v-if="alert.previous_price">
                                        Preço Anterior:
                                        <span
                                            class="font-medium text-muted-foreground line-through"
                                        >
                                            {{
                                                formatCurrency(
                                                    alert.previous_price,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preços em Destaque e Ações -->
                        <div
                            class="flex flex-col justify-between gap-3 md:items-end"
                        >
                            <div class="text-left md:text-right">
                                <span class="text-xs text-muted-foreground"
                                    >Preço Promocional:</span
                                >
                                <div
                                    class="text-xl font-bold text-green-700 dark:text-green-400"
                                >
                                    {{ formatCurrency(alert.triggered_price) }}
                                </div>
                                <div
                                    v-if="alert.installment_price"
                                    class="text-xs text-muted-foreground"
                                >
                                    A prazo:
                                    {{
                                        formatCurrency(alert.installment_price)
                                    }}
                                </div>
                            </div>

                            <div
                                class="flex flex-wrap items-center gap-2 pt-2 md:pt-0"
                            >
                                <Button
                                    v-if="!alert.is_read"
                                    variant="outline"
                                    size="sm"
                                    class="h-8 gap-1 text-xs"
                                    :disabled="markingId === alert.id"
                                    @click="markAsRead(alert)"
                                >
                                    <Spinner v-if="markingId === alert.id" />
                                    <Check v-else class="size-3.5" />
                                    Marcar como lido
                                </Button>

                                <Button
                                    as-child
                                    variant="outline"
                                    size="sm"
                                    class="h-8 gap-1 text-xs"
                                >
                                    <Link
                                        :href="
                                            productShowRoute.url({
                                                product: alert.product.id,
                                            })
                                        "
                                    >
                                        Detalhes
                                    </Link>
                                </Button>

                                <a
                                    :href="alert.product.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex h-8 items-center gap-1 rounded-md border border-input bg-background px-2.5 text-xs font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground"
                                >
                                    <ExternalLink class="size-3.5" />
                                    Ir à Loja
                                </a>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
