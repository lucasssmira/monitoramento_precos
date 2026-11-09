<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Activity,
    CheckCircle2,
    ChevronDown,
    Clock,
    Cpu,
    ExternalLink,
    Gamepad2,
    Headphones,
    LineChart,
    Monitor,
    MoreVertical,
    Package,
    PackageSearch,
    Pause,
    Pencil,
    Play,
    Plus,
    RefreshCw,
    Search,
    Smartphone,
    Target,
    Trash2,
    TrendingDown,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import MonitorProductDialog from '@/components/MonitorProductDialog.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Spinner } from '@/components/ui/spinner';
import {
    checkNow as checkNowRoute,
    destroy as destroyRoute,
    index as productsIndex,
    show as showRoute,
    toggleStatus as toggleStatusRoute,
    update as updateRoute,
} from '@/routes/products';

interface ProductItem {
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

const props = defineProps<{
    products: ProductItem[];
    categories: string[];
    statuses: Record<string, string>;
    filters: {
        category: string;
        status: string;
    };
    maxProducts: number;
    status?: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Produtos Monitorados',
                href: productsIndex(),
            },
        ],
    },
});

const isCreateModalOpen = ref(false);
const searchQuery = ref('');
const filterCategory = ref(props.filters.category ?? 'all');
const filterStatus = ref(props.filters.status ?? 'all');

const selectCategory = (cat: string): void => {
    filterCategory.value = cat;
    applyFilters();
};

const applyFilters = (): void => {
    router.get(
        productsIndex(),
        {
            category:
                filterCategory.value !== 'all'
                    ? filterCategory.value
                    : undefined,
            status:
                filterStatus.value !== 'all' &&
                filterStatus.value !== 'target_hit'
                    ? filterStatus.value
                    : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = (): void => {
    searchQuery.value = '';
    filterCategory.value = 'all';
    filterStatus.value = 'all';
    applyFilters();
};

const isTargetHit = (product: ProductItem): boolean => {
    return (
        product.in_stock &&
        product.current_price !== null &&
        product.current_price <= product.target_price
    );
};

// Filtered products combining server filters + instant client search & target_hit filter
const displayedProducts = computed<ProductItem[]>(() => {
    const q = searchQuery.value.trim().toLowerCase();

    return props.products.filter((product) => {
        if (filterStatus.value === 'target_hit' && !isTargetHit(product)) {
            return false;
        }

        if (!q) {
            return true;
        }

        const titleMatch = product.title?.toLowerCase().includes(q) ?? false;
        const nickMatch = product.nickname?.toLowerCase().includes(q) ?? false;
        const domainMatch = product.domain.toLowerCase().includes(q);
        const categoryMatch = product.category.toLowerCase().includes(q);

        return titleMatch || nickMatch || domainMatch || categoryMatch;
    });
});

// KPI calculations based on current user's products
const activeCount = computed(
    () => props.products.filter((p) => p.status === 'active').length,
);

const pausedCount = computed(
    () => props.products.filter((p) => p.status === 'paused').length,
);

const targetHitCount = computed(
    () => props.products.filter((p) => isTargetHit(p)).length,
);

const bestSavingsStats = computed(() => {
    let maxDiscountPct = 0;
    let totalAccumulatedDiscount = 0;

    for (const p of props.products) {
        if (
            p.in_stock &&
            p.current_price !== null &&
            p.target_price > 0 &&
            p.current_price < p.target_price
        ) {
            const diff = p.target_price - p.current_price;
            totalAccumulatedDiscount += diff;
            const pct = Math.round((diff / p.target_price) * 100);
            if (pct > maxDiscountPct) {
                maxDiscountPct = pct;
            }
        }
    }

    return {
        maxDiscountPct,
        totalAccumulatedDiscount,
    };
});

// Edit Modal
const editingProduct = ref<ProductItem | null>(null);
const isEditDialogOpen = ref(false);

const editForm = useForm({
    target_price: '',
    nickname: '',
    category: '',
    notify_on_stock: false,
});

const openEditModal = (product: ProductItem): void => {
    editingProduct.value = product;
    editForm.target_price = product.target_price.toString();
    editForm.nickname = product.nickname ?? '';
    editForm.category = product.category;
    editForm.notify_on_stock = product.notify_on_stock;
    editForm.clearErrors();
    isEditDialogOpen.value = true;
};

const submitEdit = (): void => {
    if (!editingProduct.value) {
        return;
    }

    editForm.put(updateRoute.url({ product: editingProduct.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
            editingProduct.value = null;
        },
    });
};

// Delete Modal
const deletingProduct = ref<ProductItem | null>(null);
const isDeleteDialogOpen = ref(false);
const isDeleting = ref(false);

const openDeleteModal = (product: ProductItem): void => {
    deletingProduct.value = product;
    isDeleteDialogOpen.value = true;
};

const confirmDelete = (): void => {
    if (!deletingProduct.value) {
        return;
    }

    isDeleting.value = true;
    router.delete(destroyRoute.url({ product: deletingProduct.value.id }), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            isDeleteDialogOpen.value = false;
            deletingProduct.value = null;
        },
    });
};

// Pause / Resume
const togglingId = ref<number | null>(null);

const toggleMonitoring = (product: ProductItem): void => {
    togglingId.value = product.id;
    router.patch(
        toggleStatusRoute.url({ product: product.id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                togglingId.value = null;
            },
        },
    );
};

// Check Now
const checkingNowId = ref<number | null>(null);

const requestCheckNow = (product: ProductItem): void => {
    checkingNowId.value = product.id;
    router.post(
        checkNowRoute.url({ product: product.id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                checkingNowId.value = null;
            },
        },
    );
};

// Helpers
const formatCurrency = (value: number | null): string => {
    if (value === null || Number.isNaN(value)) {
        return 'Aguardando...';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
};

const formatRelativeCheck = (product: ProductItem): string => {
    if (product.status === 'pending' && !product.last_checked_at) {
        return 'Indexando histórico...';
    }
    if (!product.last_checked_at) {
        return 'Nunca verificado';
    }

    try {
        const diffMs = Date.now() - new Date(product.last_checked_at).getTime();
        const diffMin = Math.max(1, Math.floor(diffMs / 60000));
        if (diffMin < 60) {
            return `Verificado há ${diffMin} min`;
        }
        const diffHours = Math.floor(diffMin / 60);
        if (diffHours < 24) {
            return `Verificado há ${diffHours} ${diffHours === 1 ? 'hora' : 'horas'}`;
        }
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'short',
            timeStyle: 'short',
        }).format(new Date(product.last_checked_at));
    } catch {
        return product.last_checked_at;
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

const getPriceDiffLabel = (
    product: ProductItem,
): { text: string; classes: string } => {
    if (product.status === 'pending' && product.current_price === null) {
        return {
            text: 'Verificando variações',
            classes: 'text-amber font-medium',
        };
    }

    if (!product.in_stock) {
        return {
            text: product.notify_on_stock
                ? 'Alerta de estoque ativo'
                : 'Alvo ativo',
            classes: 'text-text-muted',
        };
    }

    if (product.current_price === null) {
        return {
            text: 'Aguardando coleta',
            classes: 'text-text-muted',
        };
    }

    const diff = product.current_price - product.target_price;
    if (diff <= 0) {
        return {
            text: `-${formatCurrency(Math.abs(diff))} abaixo`,
            classes: 'text-primary font-semibold',
        };
    }

    return {
        text: `+${formatCurrency(diff)} da meta`,
        classes: 'text-text-muted',
    };
};
</script>

<template>
    <Head title="Monitor de Preços — Dashboard" />

    <div>
        <!-- Feedback de Status ou Rate Limit Manual -->
        <div
            v-if="status"
            class="mb-6 rounded-lg border border-primary/30 bg-primary-glow px-4 py-3 text-body-sm font-medium text-primary"
        >
            {{ status }}
        </div>

        <div
            v-if="$page.props.errors?.manual_check"
            class="mb-6 rounded-lg border border-amber/40 bg-amber-glow px-4 py-3 text-body-sm font-medium text-amber"
        >
            {{ $page.props.errors.manual_check }}
        </div>

        <!-- Faixa de Resumo Compacta (3 Cartões KPI Mínimos) -->
        <section class="mb-8 grid grid-cols-1 gap-5 md:grid-cols-3">
            <!-- KPI 1: Monitoramentos Ativos -->
            <div
                class="flex items-center justify-between rounded-lg border border-surface-border bg-surface p-5 transition-colors hover:border-surface-border/80"
            >
                <div>
                    <span
                        class="block text-body-sm font-medium text-text-secondary"
                    >
                        Monitoramentos Ativos
                    </span>
                    <div
                        class="mt-1 font-price text-price-xl font-bold text-text-primary tabular-nums"
                    >
                        {{ activeCount }} ativos
                        <span class="text-price-md font-normal text-text-muted">
                            · {{ pausedCount }} pausados
                        </span>
                    </div>
                    <span
                        class="mt-1 flex items-center gap-1.5 text-body-sm text-text-muted"
                    >
                        <span class="size-1.5 rounded-full bg-primary" />
                        Checagem automática periódica ativa
                    </span>
                </div>
                <div
                    class="flex size-12 items-center justify-center rounded-lg border border-surface-border bg-surface-elevated text-indigo"
                >
                    <Activity class="size-6" />
                </div>
            </div>

            <!-- KPI 2: Metas Atingidas Hoje (Destaque Emerald) -->
            <div
                class="relative flex items-center justify-between overflow-hidden rounded-lg border border-primary/40 bg-surface bg-gradient-to-br from-surface to-primary-glow/20 p-5 shadow-sm"
            >
                <div class="relative z-10">
                    <span
                        class="flex items-center gap-1.5 text-body-sm font-semibold text-primary"
                    >
                        <CheckCircle2 class="size-4" />
                        Metas Atingidas Agora
                    </span>
                    <div
                        class="mt-1 font-price text-price-xl font-bold text-primary tabular-nums"
                    >
                        {{ targetHitCount }}
                        {{ targetHitCount === 1 ? 'produto' : 'produtos' }}
                    </div>
                    <span class="mt-1 block text-body-sm text-primary/80">
                        no preço alvo ou abaixo
                    </span>
                </div>
                <div
                    class="relative z-10 flex size-12 items-center justify-center rounded-lg border border-primary/30 bg-primary-glow text-primary"
                >
                    <CheckCircle2 class="size-6" />
                </div>
                <div
                    class="pointer-events-none absolute -right-4 -bottom-4 size-24 rounded-full bg-primary/10 blur-xl"
                />
            </div>

            <!-- KPI 3: Maior Economia Real -->
            <div
                class="flex items-center justify-between rounded-lg border border-surface-border bg-surface p-5 transition-colors hover:border-surface-border/80"
            >
                <div>
                    <span
                        class="block text-body-sm font-medium text-text-secondary"
                    >
                        Maior Economia Real
                    </span>
                    <div
                        class="mt-1 font-price text-price-xl font-bold text-primary tabular-nums"
                    >
                        {{
                            bestSavingsStats.maxDiscountPct > 0
                                ? `-${bestSavingsStats.maxDiscountPct}%`
                                : '0%'
                        }}
                        <span class="text-price-md font-normal text-text-muted">
                            abaixo da meta
                        </span>
                    </div>
                    <span
                        class="mt-1 flex items-center gap-1 text-body-sm text-text-muted"
                    >
                        <span class="font-mono font-medium text-amber">
                            {{
                                formatCurrency(
                                    bestSavingsStats.totalAccumulatedDiscount,
                                )
                            }}
                        </span>
                        de economia potencial hoje
                    </span>
                </div>
                <div
                    class="flex size-12 items-center justify-center rounded-lg border border-surface-border bg-surface-elevated text-amber"
                >
                    <TrendingDown class="size-6" />
                </div>
            </div>
        </section>

        <!-- Barra Limpa de Busca e Filtros -->
        <section
            class="mb-6 flex flex-col items-stretch justify-between gap-4 lg:flex-row lg:items-center"
        >
            <!-- Input de Busca -->
            <div class="relative max-w-lg flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-text-muted"
                />
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Buscar por produto ou apelido..."
                    class="w-full rounded-lg border border-surface-border bg-surface py-2.5 pr-4 pl-10 font-body text-body-md text-text-primary transition-colors placeholder:text-text-muted focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                />
            </div>

            <!-- Pílulas de Categoria + Filtro de Status -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Pills de Categoria -->
                <div
                    class="flex flex-wrap items-center gap-1.5 rounded-lg border border-surface-border bg-surface p-1"
                >
                    <button
                        type="button"
                        :class="[
                            'rounded-md px-3 py-1.5 font-label text-label-sm transition-colors',
                            filterCategory === 'all'
                                ? 'border border-primary/20 bg-surface-elevated font-semibold text-primary shadow-xs'
                                : 'font-medium text-text-secondary hover:bg-surface-elevated/50 hover:text-text-primary',
                        ]"
                        @click="selectCategory('all')"
                    >
                        Todos
                    </button>
                    <button
                        v-for="cat in categories.slice(0, 4)"
                        :key="cat"
                        type="button"
                        :class="[
                            'rounded-md px-3 py-1.5 font-label text-label-sm transition-colors',
                            filterCategory === cat
                                ? 'border border-primary/20 bg-surface-elevated font-semibold text-primary shadow-xs'
                                : 'font-medium text-text-secondary hover:bg-surface-elevated/50 hover:text-text-primary',
                        ]"
                        @click="selectCategory(cat)"
                    >
                        {{ cat === 'Eletrodomésticos' ? 'Eletro' : cat }}
                    </button>
                </div>

                <!-- Dropdown de Categoria Completa (caso queira filtrar demais categorias) -->
                <div class="relative">
                    <select
                        id="filter-category"
                        v-model="filterCategory"
                        aria-label="Filtrar por categoria"
                        class="cursor-pointer appearance-none rounded-lg border border-surface-border bg-surface py-2 pr-8 pl-3.5 font-label text-label-sm text-text-primary focus:border-primary focus:outline-none"
                        @change="applyFilters"
                    >
                        <option value="all">Categoria: Todas</option>
                        <option
                            v-for="cat in categories"
                            :key="cat"
                            :value="cat"
                        >
                            {{ cat }}
                        </option>
                    </select>
                    <ChevronDown
                        class="pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2 text-text-muted"
                    />
                </div>

                <!-- Dropdown de Status -->
                <div class="relative">
                    <select
                        id="filter-status"
                        v-model="filterStatus"
                        aria-label="Filtrar por status"
                        class="cursor-pointer appearance-none rounded-lg border border-surface-border bg-surface py-2 pr-8 pl-3.5 font-label text-label-sm text-text-primary focus:border-primary focus:outline-none"
                        @change="applyFilters"
                    >
                        <option value="all">Status: Todos</option>
                        <option value="target_hit">Meta Atingida</option>
                        <option
                            v-for="(label, key) in statuses"
                            :key="key"
                            :value="key"
                        >
                            {{ label }}
                        </option>
                    </select>
                    <ChevronDown
                        class="pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2 text-text-muted"
                    />
                </div>
            </div>
        </section>

        <!-- Empty State -->
        <div
            v-if="displayedProducts.length === 0"
            class="flex flex-col items-center justify-center rounded-xl border border-surface-border bg-surface py-16 text-center"
        >
            <div
                class="mb-4 flex size-14 items-center justify-center rounded-xl border border-surface-border bg-surface-elevated text-primary"
            >
                <PackageSearch class="size-7" />
            </div>
            <h3
                class="font-title text-title-lg font-semibold text-text-primary"
            >
                Nenhum produto encontrado no radar
            </h3>
            <p class="mt-1 max-w-md px-4 text-body-sm text-text-secondary">
                <template
                    v-if="
                        filterCategory !== 'all' ||
                        filterStatus !== 'all' ||
                        searchQuery
                    "
                >
                    Não encontramos itens correspondentes aos filtros aplicados.
                </template>
                <template v-else>
                    Adicione o link de qualquer produto da Amazon, KaBuM!,
                    Mercado Livre, Magalu ou Pichau para começar a rastrear
                    quedas de preço.
                </template>
            </p>
            <div class="mt-5 flex items-center gap-3">
                <button
                    v-if="
                        filterCategory !== 'all' ||
                        filterStatus !== 'all' ||
                        searchQuery
                    "
                    type="button"
                    class="rounded-lg border border-surface-border bg-surface-elevated px-4 py-2 font-label text-label-md text-text-primary transition-colors hover:border-primary/40"
                    @click="clearFilters"
                >
                    Limpar filtros
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 font-label text-label-md font-bold text-on-primary transition-all hover:bg-[#2bbd87]"
                    @click="isCreateModalOpen = true"
                >
                    <Plus class="size-4 stroke-[2.5]" />
                    <span>+ Monitorar Produto</span>
                </button>
            </div>
        </div>

        <!-- Grade de Produtos (3 Colunas em Desktop, Espaçamento Generoso) -->
        <section
            v-else
            class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
        >
            <article
                v-for="product in displayedProducts"
                :key="product.id"
                :class="[
                    'group relative flex flex-col justify-between rounded-lg border bg-surface p-5 transition-all duration-150',
                    isTargetHit(product)
                        ? 'border-primary/40 shadow-sm hover:border-primary hover:shadow-[0_4px_20px_rgba(52,211,153,0.08)]'
                        : 'border-surface-border hover:border-text-muted/40',
                ]"
            >
                <div
                    v-if="isTargetHit(product)"
                    class="pointer-events-none absolute top-0 right-0 size-24 rounded-tr-lg bg-primary/5"
                />

                <div>
                    <!-- Header do Card -->
                    <div class="mb-3.5 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span
                                :class="[
                                    'rounded-full border px-2.5 py-0.5 font-label text-[11px] font-bold tracking-wide',
                                    getStoreBadge(product.domain).classes,
                                ]"
                            >
                                {{ getStoreBadge(product.domain).label }}
                            </span>
                            <span
                                class="font-label text-label-sm text-text-muted"
                            >
                                {{ product.category }}
                            </span>
                        </div>

                        <!-- Status Pill Direita -->
                        <span
                            v-if="product.status === 'pending'"
                            class="inline-flex items-center gap-1.5 rounded-full border border-amber/30 bg-amber-glow px-2.5 py-0.5 font-label text-label-sm font-semibold text-amber"
                        >
                            <span
                                class="size-1.5 animate-ping rounded-full bg-amber"
                            />
                            Coletando dados...
                        </span>
                        <span
                            v-else-if="!product.in_stock"
                            class="inline-flex items-center gap-1.5 rounded-full border border-coral/30 bg-coral-glow px-2.5 py-0.5 font-label text-label-sm font-semibold text-coral"
                        >
                            <span class="size-1.5 rounded-full bg-coral" />
                            Esgotado
                        </span>
                        <span
                            v-else-if="isTargetHit(product)"
                            class="inline-flex items-center gap-1.5 rounded-full border border-primary/25 bg-primary-glow px-2.5 py-0.5 font-label text-label-sm font-semibold text-primary"
                        >
                            <span
                                class="size-1.5 animate-pulse rounded-full bg-primary"
                            />
                            Meta Atingida!
                        </span>
                        <span
                            v-else-if="product.status === 'paused'"
                            class="inline-flex items-center gap-1.5 rounded-full border border-surface-border bg-surface-elevated px-2.5 py-0.5 font-label text-label-sm font-medium text-text-muted"
                        >
                            <span class="size-1.5 rounded-full bg-text-muted" />
                            Pausado
                        </span>
                        <span
                            v-else-if="product.status === 'unstable'"
                            class="inline-flex items-center gap-1.5 rounded-full border border-coral/30 bg-coral-glow px-2.5 py-0.5 font-label text-label-sm font-semibold text-coral"
                        >
                            <span class="size-1.5 rounded-full bg-coral" />
                            Instável
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 rounded-full border border-surface-border bg-surface-elevated px-2.5 py-0.5 font-label text-label-sm font-medium text-text-secondary"
                        >
                            <span
                                class="size-1.5 rounded-full bg-text-secondary"
                            />
                            Em estoque
                        </span>
                    </div>

                    <!-- Informações do Produto (Thumbnail 56x56 + Título 2 linhas) -->
                    <div class="mb-4 flex items-start gap-3.5">
                        <div
                            :class="[
                                'flex size-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-surface-border bg-surface-elevated p-2 transition-transform group-hover:scale-105',
                                !product.in_stock
                                    ? 'text-coral'
                                    : isTargetHit(product)
                                      ? 'text-primary'
                                      : product.status === 'pending'
                                        ? 'text-amber'
                                        : 'text-indigo',
                            ]"
                        >
                            <img
                                v-if="product.image_url"
                                :src="product.image_url"
                                :alt="
                                    product.nickname ||
                                    product.title ||
                                    'Produto'
                                "
                                class="size-full object-contain"
                                loading="lazy"
                            />
                            <component
                                :is="getCategoryIcon(product.category)"
                                v-else
                                class="size-7"
                            />
                        </div>

                        <div class="min-w-0 flex-1">
                            <Link
                                :href="
                                    showRoute.url({
                                        product: product.id,
                                    })
                                "
                                class="block"
                            >
                                <h3
                                    class="line-clamp-2 font-title text-title-md leading-snug font-semibold text-text-primary transition-colors group-hover:text-primary"
                                >
                                    {{
                                        product.title ||
                                        product.nickname ||
                                        product.normalized_url
                                    }}
                                </h3>
                            </Link>
                            <span
                                v-if="product.nickname"
                                class="mt-0.5 block truncate text-body-sm text-text-muted"
                            >
                                Apelido: {{ product.nickname }}
                            </span>
                            <span
                                v-else
                                class="mt-0.5 block truncate text-body-sm text-text-muted"
                            >
                                {{ product.domain }}
                            </span>
                        </div>
                    </div>

                    <!-- Bloco de Comparação de Preços (Foco Visual) -->
                    <div
                        class="mb-4 grid grid-cols-2 items-center gap-3 rounded-lg border border-surface-border bg-canvas/80 p-3.5"
                    >
                        <!-- Lado Esquerdo (Preço à vista PIX) -->
                        <div>
                            <span
                                class="mb-0.5 block text-[11px] font-medium text-text-secondary"
                            >
                                {{
                                    !product.in_stock
                                        ? 'Último Preço (PIX)'
                                        : 'Preço à vista (PIX)'
                                }}
                            </span>
                            <div
                                :class="[
                                    'font-price text-price-lg font-bold tabular-nums',
                                    !product.in_stock
                                        ? 'text-text-secondary'
                                        : isTargetHit(product)
                                          ? 'text-primary'
                                          : 'text-text-primary',
                                ]"
                            >
                                {{ formatCurrency(product.current_price) }}
                            </div>
                            <span
                                v-if="
                                    !product.in_stock && product.notify_on_stock
                                "
                                class="mt-0.5 block text-[11px] font-medium text-coral"
                            >
                                Avisar ao voltar estoque
                            </span>
                            <span
                                v-else-if="product.installment_price"
                                class="mt-0.5 block text-[11px] text-text-muted"
                            >
                                ou
                                {{ formatCurrency(product.installment_price) }}
                                a prazo
                            </span>
                            <span
                                v-else
                                class="mt-0.5 block text-[11px] text-text-muted"
                            >
                                Cotação à vista
                            </span>
                        </div>

                        <!-- Lado Direito (Preço Alvo do Usuário) -->
                        <div class="border-l border-surface-border pl-3">
                            <span
                                class="mb-0.5 flex items-center gap-1 text-[11px] font-medium text-text-secondary"
                            >
                                <Target class="size-3.5 text-amber" />
                                Preço Alvo
                            </span>
                            <div
                                class="font-price text-price-md font-semibold text-amber tabular-nums"
                            >
                                {{ formatCurrency(product.target_price) }}
                            </div>
                            <span
                                :class="[
                                    'mt-0.5 block font-mono text-[11px]',
                                    getPriceDiffLabel(product).classes,
                                ]"
                            >
                                {{ getPriceDiffLabel(product).text }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Footer do Card -->
                <div
                    class="flex items-center justify-between border-t border-surface-border pt-3 text-text-secondary"
                >
                    <span
                        class="flex items-center gap-1.5 text-[12px] text-text-muted"
                    >
                        <RefreshCw
                            v-if="product.status === 'pending'"
                            class="size-3.5 animate-spin text-amber"
                        />
                        <Clock v-else class="size-3.5" />
                        {{ formatRelativeCheck(product) }}
                    </span>

                    <div class="flex items-center gap-1">
                        <!-- Verificar Agora -->
                        <button
                            type="button"
                            title="Verificar agora"
                            :disabled="
                                checkingNowId === product.id ||
                                product.status === 'paused'
                            "
                            class="rounded-md p-1.5 transition-colors hover:bg-surface-elevated hover:text-primary disabled:opacity-40"
                            @click="requestCheckNow(product)"
                        >
                            <Spinner
                                v-if="checkingNowId === product.id"
                                class="size-4"
                            />
                            <RefreshCw v-else class="size-4" />
                        </button>

                        <!-- Pausar / Retomar -->
                        <button
                            type="button"
                            :title="
                                product.status === 'paused'
                                    ? 'Retomar monitoramento'
                                    : 'Pausar monitoramento'
                            "
                            :disabled="togglingId === product.id"
                            class="rounded-md p-1.5 transition-colors hover:bg-surface-elevated hover:text-amber disabled:opacity-40"
                            @click="toggleMonitoring(product)"
                        >
                            <Spinner
                                v-if="togglingId === product.id"
                                class="size-4"
                            />
                            <Play
                                v-else-if="product.status === 'paused'"
                                class="size-4 text-primary"
                            />
                            <Pause v-else class="size-4" />
                        </button>

                        <!-- Ver Histórico -->
                        <Link
                            :href="
                                showRoute.url({
                                    product: product.id,
                                })
                            "
                            title="Ver Histórico e Evolução"
                            class="rounded-md p-1.5 transition-colors hover:bg-surface-elevated hover:text-indigo"
                        >
                            <LineChart class="size-4" />
                        </Link>

                        <!-- Menu de 3 pontos (Editar, Ir para Loja, Excluir) -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    title="Mais opções"
                                    class="rounded-md p-1.5 transition-colors hover:bg-surface-elevated hover:text-text-primary"
                                >
                                    <MoreVertical class="size-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                class="w-44 border-surface-border bg-surface-elevated text-text-primary"
                            >
                                <DropdownMenuItem
                                    class="cursor-pointer gap-2"
                                    @click="openEditModal(product)"
                                >
                                    <Pencil class="size-4 text-amber" />
                                    <span>Editar Meta</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <a
                                        :href="product.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex w-full cursor-pointer items-center gap-2"
                                    >
                                        <ExternalLink
                                            class="size-4 text-primary"
                                        />
                                        <span>Ir para a Loja</span>
                                    </a>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator
                                    class="bg-surface-border"
                                />
                                <DropdownMenuItem
                                    class="cursor-pointer gap-2 text-coral focus:text-coral"
                                    @click="openDeleteModal(product)"
                                >
                                    <Trash2 class="size-4" />
                                    <span>Excluir</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </article>
        </section>
    </div>

    <!-- Modal Local de Cadastro (acionado no Empty State) -->
    <MonitorProductDialog v-model:open="isCreateModalOpen" />

    <!-- Modal de Edição de Parâmetros -->
    <Dialog v-model:open="isEditDialogOpen">
        <DialogContent
            class="border-surface-border bg-surface-elevated text-text-primary sm:max-w-md"
        >
            <DialogHeader>
                <DialogTitle
                    class="font-headline text-headline-md text-text-primary"
                >
                    Editar Parâmetros do Produto
                </DialogTitle>
                <DialogDescription class="text-body-sm text-text-secondary">
                    Altere o preço alvo, apelido, categoria e preferências de
                    alerta. O histórico de preços permanece intacto.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4 pt-2" @submit.prevent="submitEdit">
                <div>
                    <label
                        for="edit-target-price"
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
                            id="edit-target-price"
                            v-model="editForm.target_price"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            placeholder="1500.00"
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
                        for="edit-nickname"
                        class="mb-1.5 block font-label text-label-md text-text-secondary"
                    >
                        Apelido (Opcional)
                    </label>
                    <input
                        id="edit-nickname"
                        v-model="editForm.nickname"
                        type="text"
                        maxlength="120"
                        placeholder="Ex: Placa de Vídeo para Upgrade"
                        class="w-full rounded-lg border border-surface-border bg-surface px-3.5 py-2.5 font-body text-body-sm text-text-primary focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                    />
                    <InputError
                        :message="editForm.errors.nickname"
                        class="mt-1"
                    />
                </div>

                <div>
                    <label
                        for="edit-category"
                        class="mb-1.5 block font-label text-label-md text-text-secondary"
                    >
                        Categoria
                    </label>
                    <select
                        id="edit-category"
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
                    <InputError
                        :message="editForm.errors.category"
                        class="mt-1"
                    />
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
                            id="edit-notify-on-stock"
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

    <!-- Modal de Confirmação de Exclusão -->
    <Dialog v-model:open="isDeleteDialogOpen">
        <DialogContent
            class="border-surface-border bg-surface-elevated text-text-primary sm:max-w-md"
        >
            <DialogHeader>
                <DialogTitle
                    class="font-headline text-headline-md text-text-primary"
                >
                    Excluir produto monitorado?
                </DialogTitle>
                <DialogDescription class="text-body-sm text-text-secondary">
                    Tem certeza que deseja remover
                    <strong class="text-text-primary">{{
                        deletingProduct?.nickname ||
                        deletingProduct?.title ||
                        deletingProduct?.normalized_url
                    }}</strong>
                    e todo o seu histórico de preços? Esta ação não pode ser
                    desfeita.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2 pt-4">
                <button
                    type="button"
                    :disabled="isDeleting"
                    class="rounded-lg px-4 py-2 font-label text-label-md text-text-secondary transition-colors hover:bg-surface hover:text-text-primary"
                    @click="isDeleteDialogOpen = false"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    :disabled="isDeleting"
                    data-test="confirm-delete-product"
                    class="inline-flex items-center gap-2 rounded-lg bg-coral px-4 py-2 font-label text-label-md font-bold text-on-coral transition-opacity hover:opacity-90"
                    @click="confirmDelete"
                >
                    <Spinner v-if="isDeleting" class="size-4 text-on-coral" />
                    <span>Confirmar exclusão</span>
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
