<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    Bell,
    CheckCircle2,
    LineChart,
    PackageSearch,
    Radar,
    Settings,
} from '@lucide/vue';
import { computed } from 'vue';
import { dashboard } from '@/routes';
import { index as alertsIndex } from '@/routes/alerts';
import { index as productsIndex } from '@/routes/products';
import { edit as profileEdit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Painel',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();
const summary = computed(() => page.props.monitoringSummary);
const monitoredCount = computed(() => summary.value?.monitoredCount ?? 0);
const maxProducts = computed(() => summary.value?.maxProducts ?? 20);
const unreadAlertsCount = computed(() => summary.value?.unreadAlertsCount ?? 0);
const recentAlerts = computed(() => summary.value?.recentAlerts ?? []);
</script>

<template>
    <Head title="Painel — Monitor de Preços" />

    <div class="space-y-8">
        <!-- Hero Card do Painel -->
        <section
            class="relative overflow-hidden rounded-xl border border-primary/30 bg-surface bg-gradient-to-br from-surface via-surface to-primary-glow/20 p-6 md:p-8"
        >
            <div
                class="relative z-10 flex flex-col justify-between gap-6 md:flex-row md:items-center"
            >
                <div class="max-w-2xl">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-primary/30 bg-primary-glow px-3 py-1 font-label text-label-sm font-semibold text-primary"
                    >
                        <Radar class="size-3.5 animate-pulse" />
                        Radar Inteligente Ativo
                    </span>
                    <h1
                        class="mt-3 font-headline text-headline-lg font-bold text-text-primary"
                    >
                        Bem-vindo ao seu Painel de Monitoramento
                    </h1>
                    <p class="mt-2 text-body-md text-text-secondary">
                        Acompanhe em tempo real variações de preços à vista
                        (PIX) e parcelados nas principais lojas do Brasil, com
                        alertas automáticos quando sua meta for atingida.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="productsIndex()"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 font-label text-label-md font-bold text-on-primary shadow-sm transition-all hover:bg-[#2bbd87] hover:shadow-[0_0_15px_rgba(52,211,153,0.3)]"
                    >
                        <PackageSearch class="size-4" />
                        <span>Abrir Radar de Produtos</span>
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        :href="alertsIndex()"
                        class="inline-flex items-center gap-2 rounded-lg border border-surface-border bg-surface-elevated px-4 py-2.5 font-label text-label-md font-medium text-text-primary transition-colors hover:border-amber/40 hover:text-amber"
                    >
                        <Bell class="size-4 text-amber" />
                        <span>Central de Alertas</span>
                    </Link>
                </div>
            </div>
            <div
                class="pointer-events-none absolute -right-8 -bottom-8 size-40 rounded-full bg-primary/10 blur-2xl"
            />
        </section>

        <!-- 3 Cartões de Resumo -->
        <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <div
                class="flex items-center justify-between rounded-lg border border-surface-border bg-surface p-5"
            >
                <div>
                    <span
                        class="block text-body-sm font-medium text-text-secondary"
                    >
                        Capacidade Utilizada
                    </span>
                    <div
                        class="mt-1 font-price text-price-xl font-bold text-text-primary tabular-nums"
                    >
                        {{ monitoredCount }}
                        <span class="text-price-md font-normal text-text-muted">
                            / {{ maxProducts }} produtos
                        </span>
                    </div>
                    <span class="mt-1 block text-body-sm text-text-muted">
                        Cota disponível na sua conta
                    </span>
                </div>
                <div
                    class="flex size-12 items-center justify-center rounded-lg border border-surface-border bg-surface-elevated text-primary"
                >
                    <Activity class="size-6" />
                </div>
            </div>

            <div
                class="flex items-center justify-between rounded-lg border border-surface-border bg-surface p-5"
            >
                <div>
                    <span
                        class="block text-body-sm font-medium text-text-secondary"
                    >
                        Alertas Não Lidos
                    </span>
                    <div
                        class="mt-1 font-price text-price-xl font-bold text-amber tabular-nums"
                    >
                        {{ unreadAlertsCount }}
                        <span class="text-price-md font-normal text-text-muted">
                            notificações
                        </span>
                    </div>
                    <span class="mt-1 block text-body-sm text-text-muted">
                        {{ recentAlerts.length }} alertas recentes registrados
                    </span>
                </div>
                <div
                    class="flex size-12 items-center justify-center rounded-lg border border-amber/30 bg-amber-glow text-amber"
                >
                    <Bell class="size-6" />
                </div>
            </div>

            <div
                class="flex items-center justify-between rounded-lg border border-surface-border bg-surface p-5"
            >
                <div>
                    <span
                        class="block text-body-sm font-medium text-text-secondary"
                    >
                        Lojas Homologadas
                    </span>
                    <div
                        class="mt-1 font-price text-price-xl font-bold text-indigo tabular-nums"
                    >
                        5 E-commerces
                    </div>
                    <span class="mt-1 block text-body-sm text-text-muted">
                        Amazon, KaBuM!, ML, Magalu e Pichau
                    </span>
                </div>
                <div
                    class="flex size-12 items-center justify-center rounded-lg border border-surface-border bg-surface-elevated text-indigo"
                >
                    <CheckCircle2 class="size-6" />
                </div>
            </div>
        </section>

        <!-- Atalhos de Navegação Rápida -->
        <section class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <Link
                :href="productsIndex()"
                class="group flex flex-col justify-between rounded-lg border border-surface-border bg-surface p-5 transition-all hover:border-primary/50"
            >
                <div>
                    <div
                        class="mb-3 flex size-10 items-center justify-center rounded-lg border border-primary/30 bg-primary-glow text-primary"
                    >
                        <PackageSearch class="size-5" />
                    </div>
                    <h3
                        class="font-title text-title-md font-semibold text-text-primary group-hover:text-primary"
                    >
                        Gerenciar Produtos
                    </h3>
                    <p class="mt-1 text-body-sm text-text-secondary">
                        Visualize cotações PIX e a prazo, filtre por categoria,
                        pause ou atualize preços sob demanda.
                    </p>
                </div>
                <span
                    class="mt-4 inline-flex items-center gap-1 font-label text-label-sm font-bold text-primary"
                >
                    Acessar produtos
                    <ArrowRight class="size-3.5" />
                </span>
            </Link>

            <Link
                :href="alertsIndex()"
                class="group flex flex-col justify-between rounded-lg border border-surface-border bg-surface p-5 transition-all hover:border-amber/50"
            >
                <div>
                    <div
                        class="mb-3 flex size-10 items-center justify-center rounded-lg border border-amber/30 bg-amber-glow text-amber"
                    >
                        <LineChart class="size-5" />
                    </div>
                    <h3
                        class="font-title text-title-md font-semibold text-text-primary group-hover:text-amber"
                    >
                        Histórico de Oportunidades
                    </h3>
                    <p class="mt-1 text-body-sm text-text-secondary">
                        Consulte todos os alertas de queda de preço abaixo da
                        meta e avisos de retorno ao estoque.
                    </p>
                </div>
                <span
                    class="mt-4 inline-flex items-center gap-1 font-label text-label-sm font-bold text-amber"
                >
                    Ver alertas
                    <ArrowRight class="size-3.5" />
                </span>
            </Link>

            <Link
                :href="profileEdit()"
                class="group flex flex-col justify-between rounded-lg border border-surface-border bg-surface p-5 transition-all hover:border-indigo/50"
            >
                <div>
                    <div
                        class="mb-3 flex size-10 items-center justify-center rounded-lg border border-surface-border bg-surface-elevated text-indigo"
                    >
                        <Settings class="size-5" />
                    </div>
                    <h3
                        class="font-title text-title-md font-semibold text-text-primary group-hover:text-indigo"
                    >
                        Minha Conta & Segurança
                    </h3>
                    <p class="mt-1 text-body-sm text-text-secondary">
                        Atualize seu nome, e-mail de recebimento de alertas ou
                        altere sua senha de acesso.
                    </p>
                </div>
                <span
                    class="mt-4 inline-flex items-center gap-1 font-label text-label-sm font-bold text-indigo"
                >
                    Configurar perfil
                    <ArrowRight class="size-3.5" />
                </span>
            </Link>
        </section>
    </div>
</template>
