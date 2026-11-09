<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Bell, Bolt, Link2, Package, Radar, X } from '@lucide/vue';
import { computed, watch } from 'vue';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import InputError from '@/components/InputError.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

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

const createForm = useForm({
    url: '',
    target_price: '',
    category: categories.value[0] ?? 'Hardware',
    nickname: '',
    notify_on_stock: false,
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            createForm.clearErrors();
            if (!createForm.category && categories.value.length > 0) {
                createForm.category = categories.value[0];
            }
        }
    },
);

const closeDialog = (): void => {
    emit('update:open', false);
};

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
            closeDialog();
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            class="max-w-xl border-surface-border bg-surface-elevated p-6 text-text-primary shadow-2xl sm:rounded-xl md:p-8 [&>button]:hidden"
        >
            <!-- Topo do Modal -->
            <div
                class="mb-6 flex items-center justify-between border-b border-surface-border pb-5"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-lg border border-primary/30 bg-primary-glow text-primary"
                    >
                        <Link2 class="size-5" />
                    </div>
                    <div>
                        <DialogTitle
                            class="font-headline text-headline-md leading-tight font-bold text-text-primary"
                        >
                            Adicionar Novo Produto
                        </DialogTitle>
                        <DialogDescription
                            class="mt-0.5 text-body-sm text-text-secondary"
                        >
                            Insira o link para rastrear variações e disparar
                            alertas.
                        </DialogDescription>
                    </div>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-text-muted transition-colors hover:bg-surface hover:text-text-primary"
                    title="Fechar"
                    @click="closeDialog"
                >
                    <X class="size-5" />
                </button>
            </div>

            <!-- Formulário Limpo de Monitoramento -->
            <form class="space-y-5" @submit.prevent="submitCreate">
                <!-- Campo: URL do Produto -->
                <div>
                    <label
                        for="modal-product-url"
                        class="mb-1.5 flex items-center justify-between font-label text-label-md font-medium text-text-secondary"
                    >
                        <span>URL do Produto</span>
                        <span
                            class="flex items-center gap-1 font-mono text-[11px] text-primary"
                        >
                            <Bolt class="size-3.5" />
                            Detecção automática de preço
                        </span>
                    </label>
                    <div class="relative">
                        <Link2
                            class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-text-muted"
                        />
                        <input
                            id="modal-product-url"
                            v-model="createForm.url"
                            name="url"
                            type="url"
                            required
                            placeholder="Cole o link do produto (ex: https://www.kabum.com.br/produto/...)"
                            class="w-full rounded-lg border border-surface-border bg-surface py-2.5 pr-4 pl-10 font-body text-body-sm text-text-primary transition-colors placeholder:text-text-muted focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                        />
                    </div>
                    <InputError :message="createForm.errors.url" class="mt-1" />

                    <!-- Badges de Lojas Suportadas Logo Abaixo -->
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-medium text-text-muted">
                            Lojas suportadas:
                        </span>
                        <span
                            class="rounded border border-[#FF9900]/30 bg-[#FF9900]/15 px-2 py-0.5 font-label text-[10px] font-bold text-[#FFB03B]"
                        >
                            Amazon
                        </span>
                        <span
                            class="rounded border border-[#FF6500]/30 bg-[#FF6500]/15 px-2 py-0.5 font-label text-[10px] font-bold text-[#FF8533]"
                        >
                            KaBuM!
                        </span>
                        <span
                            class="rounded border border-[#FFE600]/30 bg-[#FFE600]/15 px-2 py-0.5 font-label text-[10px] font-bold text-[#E5CE00]"
                        >
                            Mercado Livre
                        </span>
                        <span
                            class="rounded border border-[#0086FF]/30 bg-[#0086FF]/15 px-2 py-0.5 font-label text-[10px] font-bold text-[#33A1FF]"
                        >
                            Magalu
                        </span>
                        <span
                            class="rounded border border-[#E51B24]/30 bg-[#E51B24]/15 px-2 py-0.5 font-label text-[10px] font-bold text-[#FF5A62]"
                        >
                            Pichau
                        </span>
                    </div>
                </div>

                <!-- Linha em Duas Colunas: Preço Alvo (R$) + Categoria -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Preço Alvo com foco Warm Amber -->
                    <div>
                        <label
                            for="modal-target-price"
                            class="mb-1.5 flex items-center justify-between font-label text-label-md font-medium text-text-secondary"
                        >
                            <span>Preço Alvo (R$)</span>
                            <span
                                class="flex items-center gap-1 text-[11px] font-medium text-amber"
                            >
                                <Bell class="size-3.5" />
                                Gatilho
                            </span>
                        </label>
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 font-price text-sm font-semibold text-text-muted"
                            >
                                R$
                            </span>
                            <input
                                id="modal-target-price"
                                v-model="createForm.target_price"
                                name="target_price"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                placeholder="0,00"
                                class="w-full rounded-lg border border-amber/50 bg-surface py-2.5 pr-4 pl-10 font-price text-price-md font-semibold text-text-primary tabular-nums transition-colors placeholder:text-text-muted focus:border-amber focus:ring-1 focus:ring-amber focus:outline-none"
                            />
                        </div>
                        <span class="mt-1 block text-[11px] text-text-muted">
                            Você será alertado quando cair deste valor.
                        </span>
                        <InputError
                            :message="createForm.errors.target_price"
                            class="mt-1"
                        />
                    </div>

                    <!-- Categoria -->
                    <div>
                        <label
                            for="modal-category"
                            class="mb-1.5 block font-label text-label-md font-medium text-text-secondary"
                        >
                            Categoria
                        </label>
                        <div class="relative">
                            <select
                                id="modal-category"
                                v-model="createForm.category"
                                name="category"
                                required
                                class="w-full cursor-pointer appearance-none rounded-lg border border-surface-border bg-surface py-2.5 pr-8 pl-3.5 font-label text-label-md text-text-primary focus:border-primary focus:outline-none"
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
                        <span class="mt-1 block text-[11px] text-text-muted">
                            Organiza seus cards no painel.
                        </span>
                        <InputError
                            :message="createForm.errors.category"
                            class="mt-1"
                        />
                    </div>
                </div>

                <!-- Apelido do Produto (Opcional) -->
                <div>
                    <label
                        for="modal-nickname"
                        class="mb-1.5 flex items-center justify-between font-label text-label-md font-medium text-text-secondary"
                    >
                        <span>Apelido do Produto</span>
                        <span class="text-[11px] font-normal text-text-muted">
                            (Opcional)
                        </span>
                    </label>
                    <input
                        id="modal-nickname"
                        v-model="createForm.nickname"
                        name="nickname"
                        type="text"
                        maxlength="120"
                        placeholder="Ex: Dell 4K Escritório ou RTX 4070 Setup"
                        class="w-full rounded-lg border border-surface-border bg-surface px-3.5 py-2.5 font-body text-body-sm text-text-primary transition-colors placeholder:text-text-muted focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                    />
                    <InputError
                        :message="createForm.errors.nickname"
                        class="mt-1"
                    />
                </div>

                <!-- Toggle Switch Suave: Avisar também quando voltar ao estoque -->
                <div
                    class="flex items-center justify-between rounded-lg border border-surface-border bg-surface p-3.5"
                >
                    <div class="flex items-center gap-2.5">
                        <Package class="size-5 shrink-0 text-amber" />
                        <div>
                            <span
                                class="block font-label text-label-md font-medium text-text-primary"
                            >
                                Avisar também quando voltar ao estoque
                            </span>
                            <span
                                class="text-body-sm text-[11px] text-text-muted"
                            >
                                Disparar alerta prioritário caso o produto
                                esteja esgotado.
                            </span>
                        </div>
                    </div>
                    <label
                        class="relative inline-flex cursor-pointer items-center"
                    >
                        <input
                            v-model="createForm.notify_on_stock"
                            name="notify_on_stock"
                            type="checkbox"
                            class="peer sr-only"
                        />
                        <div
                            class="h-6 w-11 rounded-full border border-surface-border bg-canvas peer-checked:bg-primary peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:size-5 after:rounded-full after:border after:border-gray-300 after:bg-text-primary after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white"
                        />
                    </label>
                </div>

                <!-- Botões de Ação do Modal -->
                <div class="flex items-center justify-end gap-3 pt-3">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2.5 font-label text-label-md font-medium text-text-secondary transition-colors hover:bg-surface hover:text-text-primary"
                        @click="closeDialog"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        data-test="store-product-button"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 font-label text-label-md font-bold text-on-primary shadow-md transition-all hover:bg-[#2bbd87] hover:shadow-[0_0_15px_rgba(52,211,153,0.3)] disabled:opacity-50"
                    >
                        <Spinner
                            v-if="createForm.processing"
                            class="size-4 text-on-primary"
                        />
                        <Radar v-else class="size-4" />
                        <span>Iniciar Monitoramento</span>
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
