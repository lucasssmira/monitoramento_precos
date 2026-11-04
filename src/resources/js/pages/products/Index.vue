<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index as productsIndex } from '@/routes/products';

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
    created_at: string | null;
}

const props = defineProps<{
    products: ProductItem[];
    categories: string[];
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

const form = useForm({
    url: '',
    target_price: '',
    category: props.categories[0] ?? 'Hardware',
    nickname: '',
    notify_on_stock: false,
});

const submit = (): void => {
    form.post(ProductController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('url', 'target_price', 'nickname', 'notify_on_stock');
        },
    });
};

const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
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
                description="Cadastre URLs de produtos das lojas suportadas para acompanhar preços à vista (PIX) e a prazo."
            />
            <Badge variant="secondary" class="w-fit">
                {{ products.length }} / {{ maxProducts }} produtos ativos
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

                <form
                    class="grid gap-4 md:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="url">URL do produto</Label>
                        <Input
                            id="url"
                            v-model="form.url"
                            name="url"
                            type="url"
                            required
                            placeholder="https://www.kabum.com.br/produto/..."
                        />
                        <InputError :message="form.errors.url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="target_price">Preço Alvo (R$)</Label>
                        <Input
                            id="target_price"
                            v-model="form.target_price"
                            name="target_price"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            placeholder="1500.00"
                        />
                        <InputError :message="form.errors.target_price" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="category">Categoria</Label>
                        <select
                            id="category"
                            v-model="form.category"
                            name="category"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                        >
                            <option
                                v-for="category in categories"
                                :key="category"
                                :value="category"
                            >
                                {{ category }}
                            </option>
                        </select>
                        <InputError :message="form.errors.category" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="nickname">Apelido (opcional)</Label>
                        <Input
                            id="nickname"
                            v-model="form.nickname"
                            name="nickname"
                            type="text"
                            maxlength="120"
                            placeholder="Ex: Placa de Vídeo para Upgrade"
                        />
                        <InputError :message="form.errors.nickname" />
                    </div>

                    <div class="flex items-center space-x-3 pt-6">
                        <input
                            id="notify_on_stock"
                            v-model="form.notify_on_stock"
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
                            :disabled="form.processing"
                            data-test="store-product-button"
                        >
                            <Spinner v-if="form.processing" />
                            Iniciar Monitoramento
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <div class="grid gap-4">
            <Card v-if="products.length === 0">
                <CardContent
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    Nenhum produto cadastrado ainda. Informe uma URL acima para
                    iniciar o monitoramento.
                </CardContent>
            </Card>

            <Card v-for="product in products" :key="product.id">
                <CardContent
                    class="flex flex-col justify-between gap-4 py-4 sm:flex-row sm:items-center"
                >
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">
                                {{
                                    product.nickname ||
                                    product.title ||
                                    product.normalized_url
                                }}
                            </span>
                            <Badge variant="outline">{{
                                product.category
                            }}</Badge>
                            <Badge variant="secondary">{{
                                product.domain
                            }}</Badge>
                        </div>
                        <p class="text-xs break-all text-muted-foreground">
                            {{ product.normalized_url }}
                        </p>
                    </div>

                    <div class="flex flex-col items-start gap-1 sm:items-end">
                        <span class="text-sm font-medium">
                            Preço Alvo:
                            {{ formatCurrency(product.target_price) }}
                        </span>
                        <Badge>{{ product.status_label }}</Badge>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
