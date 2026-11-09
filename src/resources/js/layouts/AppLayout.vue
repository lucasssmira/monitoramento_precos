<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, Plus, Radar } from '@lucide/vue';
import { computed, ref } from 'vue';
import AlertsSlideOver from '@/components/AlertsSlideOver.vue';
import MonitorProductDialog from '@/components/MonitorProductDialog.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { index as productsIndex } from '@/routes/products';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();

const user = computed(() => page.props.auth?.user);
const summary = computed(() => page.props.monitoringSummary);

const monitoredCount = computed(() => summary.value?.monitoredCount ?? 0);
const maxProducts = computed(() => summary.value?.maxProducts ?? 20);
const unreadAlertsCount = computed(() => summary.value?.unreadAlertsCount ?? 0);

const quotaPercentage = computed(() => {
    if (maxProducts.value <= 0) {
        return 0;
    }
    return Math.min(
        100,
        Math.round((monitoredCount.value / maxProducts.value) * 100),
    );
});

const isMonitorDialogOpen = ref(false);
const isAlertsDrawerOpen = ref(false);
</script>

<template>
    <div
        class="min-h-screen bg-canvas font-body text-text-primary antialiased selection:bg-primary/20 selection:text-primary"
    >
        <!-- Topbar Minimalista Unificado -->
        <header
            class="sticky top-0 z-40 border-b border-surface-border bg-surface/90 backdrop-blur-md"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6"
            >
                <!-- Esquerda: Logo Pulse/Radar + Título -->
                <Link
                    :href="productsIndex()"
                    class="flex items-center gap-3 transition-opacity hover:opacity-90"
                >
                    <div
                        class="relative flex size-9 items-center justify-center rounded-lg border border-primary/30 bg-primary-glow text-primary"
                    >
                        <Radar class="size-5 animate-pulse" />
                        <span
                            class="absolute -top-0.5 -right-0.5 size-2 rounded-full bg-primary ring-2 ring-surface"
                        />
                    </div>
                    <div>
                        <span
                            class="flex items-center gap-1.5 font-headline text-headline-md font-bold tracking-tight text-text-primary"
                        >
                            Monitor de Preços
                            <span
                                class="rounded border border-primary/20 bg-primary/10 px-1.5 py-0.5 font-mono text-[10px] font-bold tracking-wider text-primary uppercase"
                            >
                                PRO
                            </span>
                        </span>
                        <span
                            class="hidden text-body-sm text-text-muted sm:block"
                        >
                            Inteligência de Varejo em Tempo Real
                        </span>
                    </div>
                </Link>

                <!-- Centro-Direita: Quota de Conta com Progresso -->
                <div
                    class="hidden items-center gap-3 rounded-full border border-surface-border bg-surface-elevated/70 px-3.5 py-1.5 md:flex"
                >
                    <div class="flex flex-col">
                        <div
                            class="flex items-center justify-between gap-2 text-body-sm"
                        >
                            <span
                                class="text-[12px] font-medium text-text-secondary"
                            >
                                Capacidade do Radar:
                            </span>
                            <span
                                class="font-price text-[12px] font-semibold text-text-primary tabular-nums"
                            >
                                {{ monitoredCount }} / {{ maxProducts }}
                                monitorados
                            </span>
                        </div>
                        <div
                            class="mt-1 h-1.5 w-36 overflow-hidden rounded-full bg-canvas"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-primary to-amber transition-all duration-300"
                                :style="{ width: `${quotaPercentage}%` }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Direita: Notificações, CTA Primário e Avatar -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <!-- Botão Notificações com Dot Âmbar -->
                    <button
                        type="button"
                        class="relative rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-text-primary"
                        title="Central de Alertas"
                        data-test="open-alerts-drawer"
                        @click="isAlertsDrawerOpen = true"
                    >
                        <Bell class="size-5" />
                        <span
                            v-if="unreadAlertsCount > 0"
                            class="absolute top-1.5 right-1.5 size-2.5 animate-pulse rounded-full bg-amber shadow-[0_0_8px_rgba(251,191,36,0.8)]"
                        />
                    </button>

                    <!-- Botão CTA Primário -->
                    <button
                        type="button"
                        data-test="open-monitor-modal"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 font-label text-label-md font-bold text-on-primary shadow-sm transition-all duration-150 hover:bg-[#2bbd87] hover:shadow-[0_0_15px_rgba(52,211,153,0.3)] sm:px-4"
                        @click="isMonitorDialogOpen = true"
                    >
                        <Plus class="size-4 stroke-[2.5]" />
                        <span class="hidden sm:inline"
                            >+ Monitorar Produto</span
                        >
                        <span class="sm:hidden">Monitorar</span>
                    </button>

                    <!-- Avatar e Menu do Usuário -->
                    <div
                        v-if="user"
                        class="flex items-center gap-2 border-l border-surface-border pl-2.5"
                    >
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    type="button"
                                    class="flex size-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo to-primary/40 p-0.5 text-xs font-bold text-text-primary transition-transform hover:scale-105 focus:ring-2 focus:ring-primary focus:outline-none"
                                    :title="user.name"
                                >
                                    <span
                                        class="flex size-full items-center justify-center rounded-full bg-surface"
                                    >
                                        {{ getInitials(user.name) }}
                                    </span>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                align="end"
                                class="w-56 border-surface-border bg-surface-elevated text-text-primary"
                            >
                                <UserMenuContent :user="user" />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>
        </header>

        <!-- Conteúdo Principal -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <slot
                :open-monitor-dialog="() => (isMonitorDialogOpen = true)"
                :open-alerts-drawer="() => (isAlertsDrawerOpen = true)"
                :breadcrumbs="breadcrumbs"
            />
        </main>

        <!-- Modal Global "+ Monitorar Produto" -->
        <MonitorProductDialog v-model:open="isMonitorDialogOpen" />

        <!-- Slide-over Global "Central de Alertas" -->
        <AlertsSlideOver v-model:open="isAlertsDrawerOpen" />

        <Toaster />
    </div>
</template>
