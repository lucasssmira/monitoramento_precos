<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ExternalLink,
    Filter,
    PackageSearch,
    Pause,
    Pencil,
    Play,
    RotateCcw,
    Trash2,
    TrendingDown,
} from '@lucide/vue';
import { ref } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

const createForm = useForm({
    url: '',
    target_price: '',
    category: props.categories[0] ?? 'Hardware',
    nickname: '',
    notify_on_stock: false,
});

const submitCreate = (): void => {
    createForm.post(ProductController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset(
                'url',
                'target_price',
                'nickname',
                'notify_on_stock',
            );
        },
    });
};

const filterCategory = ref(props.filters.category ?? 'all');
const filterStatus = ref(props.filters.status ?? 'all');

const applyFilters = (): void => {
    router.get(
        productsIndex(),
        {
            category:
                filterCategory.value !== 'all'
                    ? filterCategory.value
                    : undefined,
            status:
                filterStatus.value !== 'all' ? filterStatus.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearFilters = (): void => {
    filterCategory.value = 'all';
    filterStatus.value = 'all';
    applyFilters();
};

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
    <Head title="Produtos Monitorados" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div
            class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
        >
            <Heading
                title="Produtos Monitorados"
                description="Cadastre, organize por categoria, pause ou edite seus produtos acompanhados nas lojas suportadas."
            />
            <Badge variant="secondary" class="w-fit text-sm">
                {{ products.length }} / {{ maxProducts }} produtos
            </Badge>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Cadastrar Novo Produto</CardTitle>
                <CardDescription>
                    Lojas suportadas: Amazon Brasil, Kabum, Mercado Livre,
                    Magazine Luiza e Pichau.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="status"
                    class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-700 dark:border-green-900/50 dark:bg-green-950/50 dark:text-green-400"
                >
                    {{ status }}
                </div>

                <div
                    v-if="$page.props.errors?.manual_check"
                    class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm font-medium text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-400"
                >
                    {{ $page.props.errors.manual_check }}
                </div>

                <form
                    class="grid gap-4 md:grid-cols-2"
                    @submit.prevent="submitCreate"
                >
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="url">URL do produto</Label>
                        <Input
                            id="url"
                            v-model="createForm.url"
                            name="url"
                            type="url"
                            required
                            placeholder="https://www.kabum.com.br/produto/..."
                        />
                        <InputError :message="createForm.errors.url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="target_price">Preço Alvo (R$)</Label>
                        <Input
                            id="target_price"
                            v-model="createForm.target_price"
                            name="target_price"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            placeholder="1500.00"
                        />
                        <InputError :message="createForm.errors.target_price" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="category">Categoria</Label>
                        <select
                            id="category"
                            v-model="createForm.category"
                            name="category"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        >
                            <option
                                v-for="cat in categories"
                                :key="cat"
                                :value="cat"
                            >
                                {{ cat }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.category" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="nickname">Apelido (opcional)</Label>
                        <Input
                            id="nickname"
                            v-model="createForm.nickname"
                            name="nickname"
                            type="text"
                            maxlength="120"
                            placeholder="Ex: Placa de Vídeo para Upgrade"
                        />
                        <InputError :message="createForm.errors.nickname" />
                    </div>

                    <div class="flex items-center space-x-3 pt-6">
                        <input
                            id="notify_on_stock"
                            v-model="createForm.notify_on_stock"
                            name="notify_on_stock"
                            type="checkbox"
                            class="size-4 rounded border-input text-primary focus:ring-ring"
                        />
                        <Label for="notify_on_stock" class="cursor-pointer">
                            Avisar também quando voltar ao estoque
                        </Label>
                    </div>

                    <div class="md:col-span-2">
                        <Button
                            type="submit"
                            :disabled="createForm.processing"
                            data-test="store-product-button"
                        >
                            <Spinner v-if="createForm.processing" />
                            Iniciar Monitoramento
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Filtros de Listagem -->
        <Card>
            <CardHeader class="pb-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Filter class="size-4 text-muted-foreground" />
                        <CardTitle class="text-base"
                            >Filtrar Produtos</CardTitle
                        >
                    </div>
                    <Button
                        v-if="
                            filterCategory !== 'all' || filterStatus !== 'all'
                        "
                        variant="ghost"
                        size="sm"
                        class="h-8 gap-1 text-xs"
                        @click="clearFilters"
                    >
                        <RotateCcw class="size-3" />
                        Limpar filtros
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="filter-category" class="text-xs"
                            >Categoria</Label
                        >
                        <select
                            id="filter-category"
                            v-model="filterCategory"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            @change="applyFilters"
                        >
                            <option value="all">Todas as categorias</option>
                            <option
                                v-for="cat in categories"
                                :key="cat"
                                :value="cat"
                            >
                                {{ cat }}
                            </option>
                        </select>
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="filter-status" class="text-xs"
                            >Status</Label
                        >
                        <select
                            id="filter-status"
                            v-model="filterStatus"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            @change="applyFilters"
                        >
                            <option value="all">Todos os status</option>
                            <option
                                v-for="(label, key) in statuses"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Listagem de Produtos -->
        <div class="grid gap-4">
            <Card v-if="products.length === 0">
                <CardContent
                    class="flex flex-col items-center justify-center py-12 text-center text-sm text-muted-foreground"
                >
                    <PackageSearch
                        class="mb-3 size-10 text-muted-foreground/60"
                    />
                    <p class="font-medium text-foreground">
                        Nenhum produto encontrado
                    </p>
                    <p
                        v-if="
                            filterCategory !== 'all' || filterStatus !== 'all'
                        "
                        class="mt-1"
                    >
                        Tente ajustar os filtros selecionados ou limpe a busca.
                    </p>
                    <p v-else class="mt-1">
                        Informe uma URL de loja suportada acima para começar o
                        monitoramento.
                    </p>
                </CardContent>
            </Card>

            <Card
                v-for="product in products"
                :key="product.id"
                class="overflow-hidden transition-all hover:shadow-md"
            >
                <CardContent class="p-4 sm:p-6">
                    <div
                        class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
                    >
                        <!-- Imagem e Identificação -->
                        <div class="flex items-start gap-4">
                            <div
                                class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted sm:size-24"
                            >
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="
                                        product.nickname ||
                                        product.title ||
                                        'Produto'
                                    "
                                    class="size-full object-contain p-1"
                                    loading="lazy"
                                />
                                <PackageSearch
                                    v-else
                                    class="size-8 text-muted-foreground/40"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3
                                        class="text-base leading-tight font-semibold text-foreground"
                                    >
                                        <Link
                                            :href="
                                                showRoute.url({
                                                    product: product.id,
                                                })
                                            "
                                            class="transition-colors hover:text-primary hover:underline"
                                        >
                                            {{
                                                product.nickname ||
                                                product.title ||
                                                product.normalized_url
                                            }}
                                        </Link>
                                    </h3>
                                    <Badge variant="outline" class="text-xs">
                                        {{ product.category }}
                                    </Badge>
                                    <Badge variant="secondary" class="text-xs">
                                        {{ product.domain }}
                                    </Badge>
                                </div>

                                <p
                                    v-if="product.nickname && product.title"
                                    class="line-clamp-2 text-xs text-muted-foreground"
                                >
                                    {{ product.title }}
                                </p>

                                <div
                                    class="flex flex-wrap items-center gap-2 pt-1"
                                >
                                    <Badge
                                        :variant="
                                            product.in_stock
                                                ? 'secondary'
                                                : 'destructive'
                                        "
                                        class="text-xs font-normal"
                                    >
                                        {{
                                            product.in_stock
                                                ? 'Disponível em estoque'
                                                : 'Esgotado'
                                        }}
                                    </Badge>

                                    <Badge
                                        :variant="
                                            product.status === 'active'
                                                ? 'default'
                                                : product.status === 'paused'
                                                  ? 'secondary'
                                                  : product.status ===
                                                      'unstable'
                                                    ? 'destructive'
                                                    : 'outline'
                                        "
                                        class="text-xs"
                                    >
                                        {{ product.status_label }}
                                    </Badge>

                                    <span
                                        v-if="product.notify_on_stock"
                                        class="text-xs text-muted-foreground"
                                    >
                                        • Alerta de estoque ativo
                                    </span>
                                </div>

                                <p class="pt-1 text-xs text-muted-foreground">
                                    Última checagem:
                                    {{ formatDate(product.last_checked_at) }}
                                </p>
                            </div>
                        </div>

                        <!-- Preços e Ações -->
                        <div
                            class="flex flex-col justify-between gap-3 md:items-end"
                        >
                            <div class="space-y-1 text-left md:text-right">
                                <div class="text-xs text-muted-foreground">
                                    Preço Alvo:
                                    <span class="font-medium text-foreground">{{
                                        formatCurrency(product.target_price)
                                    }}</span>
                                </div>

                                <div class="text-lg font-bold text-foreground">
                                    <span
                                        class="text-xs font-normal text-muted-foreground"
                                        >À vista:
                                    </span>
                                    {{ formatCurrency(product.current_price) }}
                                </div>

                                <div
                                    v-if="product.installment_price"
                                    class="text-xs text-muted-foreground"
                                >
                                    A prazo:
                                    {{
                                        formatCurrency(
                                            product.installment_price,
                                        )
                                    }}
                                </div>
                            </div>

                            <!-- Botões de Ação -->
                            <div
                                class="flex flex-wrap items-center gap-2 pt-2 md:pt-0"
                            >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 gap-1 text-xs"
                                    :disabled="
                                        checkingNowId === product.id ||
                                        product.status === 'paused'
                                    "
                                    title="Verificar preços agora (mínimo de 10 minutos entre checagens)"
                                    @click="requestCheckNow(product)"
                                >
                                    <Spinner
                                        v-if="checkingNowId === product.id"
                                    />
                                    <RotateCcw v-else class="size-3.5" />
                                    Verificar agora
                                </Button>

                                <Button
                                    as-child
                                    variant="outline"
                                    size="sm"
                                    class="h-8 gap-1 text-xs"
                                >
                                    <Link
                                        :href="
                                            showRoute.url({
                                                product: product.id,
                                            })
                                        "
                                    >
                                        <TrendingDown class="size-3.5" />
                                        Histórico
                                    </Link>
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

                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 gap-1 text-xs"
                                    :disabled="togglingId === product.id"
                                    @click="toggleMonitoring(product)"
                                >
                                    <Spinner v-if="togglingId === product.id" />
                                    <template v-else>
                                        <Play
                                            v-if="product.status === 'paused'"
                                            class="size-3.5"
                                        />
                                        <Pause v-else class="size-3.5" />
                                    </template>
                                    {{
                                        product.status === 'paused'
                                            ? 'Retomar'
                                            : 'Pausar'
                                    }}
                                </Button>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 gap-1 text-xs"
                                    @click="openEditModal(product)"
                                >
                                    <Pencil class="size-3.5" />
                                    Editar
                                </Button>

                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="h-8 gap-1 text-xs"
                                    @click="openDeleteModal(product)"
                                >
                                    <Trash2 class="size-3.5" />
                                    Excluir
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>

    <!-- Modal de Edição de Parâmetros -->
    <Dialog v-model:open="isEditDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Editar Parâmetros do Produto</DialogTitle>
                <DialogDescription>
                    Altere o preço alvo, apelido, categoria e preferências de
                    alerta. O histórico de preços já coletado permanece intacto.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitEdit">
                <div class="grid gap-2">
                    <Label for="edit-target-price">Preço Alvo (R$)</Label>
                    <Input
                        id="edit-target-price"
                        v-model="editForm.target_price"
                        type="number"
                        step="0.01"
                        min="0.01"
                        required
                        placeholder="1500.00"
                    />
                    <InputError :message="editForm.errors.target_price" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-nickname">Apelido (opcional)</Label>
                    <Input
                        id="edit-nickname"
                        v-model="editForm.nickname"
                        type="text"
                        maxlength="120"
                        placeholder="Ex: Placa de Vídeo para Upgrade"
                    />
                    <InputError :message="editForm.errors.nickname" />
                </div>

                <div class="grid gap-2">
                    <Label for="edit-category">Categoria</Label>
                    <select
                        id="edit-category"
                        v-model="editForm.category"
                        required
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option
                            v-for="cat in categories"
                            :key="cat"
                            :value="cat"
                        >
                            {{ cat }}
                        </option>
                    </select>
                    <InputError :message="editForm.errors.category" />
                </div>

                <div class="flex items-center space-x-3 pt-2">
                    <input
                        id="edit-notify-on-stock"
                        v-model="editForm.notify_on_stock"
                        type="checkbox"
                        class="size-4 rounded border-input text-primary focus:ring-ring"
                    />
                    <Label
                        for="edit-notify-on-stock"
                        class="cursor-pointer text-sm"
                    >
                        Avisar também quando voltar ao estoque
                    </Label>
                </div>

                <DialogFooter class="gap-2 pt-2">
                    <DialogClose as-child>
                        <Button variant="secondary" type="button">
                            Cancelar
                        </Button>
                    </DialogClose>
                    <Button type="submit" :disabled="editForm.processing">
                        <Spinner v-if="editForm.processing" />
                        Salvar alterações
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Modal de Confirmação de Exclusão -->
    <Dialog v-model:open="isDeleteDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Excluir Produto Monitorado</DialogTitle>
                <DialogDescription>
                    Tem certeza de que deseja remover
                    <strong class="text-foreground">{{
                        deletingProduct?.nickname ||
                        deletingProduct?.title ||
                        'este produto'
                    }}</strong>
                    da sua lista? Esta ação removerá definitivamente o produto e
                    todo o histórico de preços.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2 pt-2">
                <DialogClose as-child>
                    <Button variant="secondary" type="button">
                        Cancelar
                    </Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="isDeleting"
                    @click="confirmDelete"
                >
                    <Spinner v-if="isDeleting" />
                    Excluir definitivamente
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
