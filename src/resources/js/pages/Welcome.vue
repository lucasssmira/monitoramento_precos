<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bell,
    CheckCircle2,
    LineChart,
    Radar,
    TrendingDown,
} from '@lucide/vue';
import { dashboard, login, register } from '@/routes';
import { index as productsIndex } from '@/routes/products';
</script>

<template>
    <Head title="Monitor de Preços — Inteligência de Varejo em Tempo Real" />

    <div
        class="flex min-h-screen flex-col justify-between bg-canvas font-body text-text-primary antialiased selection:bg-primary/20 selection:text-primary"
    >
        <!-- Header Minimalista -->
        <header
            class="border-b border-surface-border bg-surface/90 backdrop-blur-md"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6"
            >
                <div class="flex items-center gap-3">
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
                    </div>
                </div>

                <nav class="flex items-center gap-3">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="productsIndex()"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 font-label text-label-md font-bold text-on-primary transition-all hover:bg-[#2bbd87]"
                        >
                            <span>Acessar Radar</span>
                            <ArrowRight class="size-4" />
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-lg px-4 py-2 font-label text-label-md font-medium text-text-secondary transition-colors hover:bg-surface-elevated hover:text-text-primary"
                        >
                            Entrar
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 font-label text-label-md font-bold text-on-primary shadow-sm transition-all hover:bg-[#2bbd87] hover:shadow-[0_0_15px_rgba(52,211,153,0.3)]"
                        >
                            <span>Criar conta</span>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="mx-auto max-w-7xl px-6 py-16 lg:py-24">
            <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12">
                <div class="space-y-6 lg:col-span-7">
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary-glow px-3.5 py-1 font-label text-label-sm font-semibold text-primary"
                    >
                        <span
                            class="size-1.5 animate-pulse rounded-full bg-primary"
                        />
                        Monitoramento Automatizado PIX & A Prazo
                    </span>

                    <h1
                        class="font-display text-display-lg leading-tight font-bold text-text-primary sm:text-[44px] sm:leading-[52px]"
                    >
                        Pague apenas o
                        <span class="text-primary">preço justo</span> nas
                        maiores lojas do Brasil.
                    </h1>

                    <p
                        class="max-w-xl text-body-lg leading-relaxed text-text-secondary"
                    >
                        Defina sua meta de preço alvo, acompanhe a evolução
                        histórica real de 7, 30 e 90 dias e receba alertas
                        instantâneos quando o valor no PIX cair ou o produto
                        voltar ao estoque.
                    </p>

                    <!-- Lojas Suportadas -->
                    <div class="flex flex-wrap items-center gap-2 pt-2">
                        <span class="text-body-sm font-medium text-text-muted">
                            Lojas homologadas:
                        </span>
                        <span
                            class="rounded-full border border-[#FF9900]/30 bg-[#FF9900]/15 px-2.5 py-0.5 font-label text-[11px] font-bold text-[#FFB03B]"
                        >
                            Amazon
                        </span>
                        <span
                            class="rounded-full border border-[#FF6500]/30 bg-[#FF6500]/15 px-2.5 py-0.5 font-label text-[11px] font-bold text-[#FF8533]"
                        >
                            KaBuM!
                        </span>
                        <span
                            class="rounded-full border border-[#FFE600]/30 bg-[#FFE600]/15 px-2.5 py-0.5 font-label text-[11px] font-bold text-[#E5CE00]"
                        >
                            Mercado Livre
                        </span>
                        <span
                            class="rounded-full border border-[#0086FF]/30 bg-[#0086FF]/15 px-2.5 py-0.5 font-label text-[11px] font-bold text-[#33A1FF]"
                        >
                            Magalu
                        </span>
                        <span
                            class="rounded-full border border-[#E51B24]/30 bg-[#E51B24]/15 px-2.5 py-0.5 font-label text-[11px] font-bold text-[#FF5A62]"
                        >
                            Pichau
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 pt-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-3 font-label text-label-lg font-bold text-on-primary shadow-md transition-all hover:bg-[#2bbd87] hover:shadow-[0_0_20px_rgba(52,211,153,0.35)]"
                        >
                            <span>Ir para o Painel</span>
                            <ArrowRight class="size-4" />
                        </Link>
                        <template v-else>
                            <Link
                                :href="register()"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-3 font-label text-label-lg font-bold text-on-primary shadow-md transition-all hover:bg-[#2bbd87] hover:shadow-[0_0_20px_rgba(52,211,153,0.35)]"
                            >
                                <span>Começar a Monitorar Grátis</span>
                                <ArrowRight class="size-4" />
                            </Link>
                            <Link
                                :href="login()"
                                class="inline-flex items-center gap-2 rounded-lg border border-surface-border bg-surface px-5 py-3 font-label text-label-lg font-medium text-text-primary transition-colors hover:bg-surface-elevated"
                            >
                                <span>Já tenho conta</span>
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- Preview Card Estilo Stitch -->
                <div class="lg:col-span-5">
                    <div
                        class="relative rounded-xl border border-primary/40 bg-surface p-6 shadow-2xl"
                    >
                        <div
                            class="mb-4 flex items-center justify-between gap-2"
                        >
                            <span
                                class="rounded-full border border-[#FF6500]/30 bg-[#FF6500]/15 px-2.5 py-0.5 font-label text-[11px] font-bold text-[#FF8533]"
                            >
                                KaBuM!
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-primary/25 bg-primary-glow px-2.5 py-0.5 font-label text-label-sm font-semibold text-primary"
                            >
                                <span
                                    class="size-1.5 animate-pulse rounded-full bg-primary"
                                />
                                Meta Atingida!
                            </span>
                        </div>

                        <h3
                            class="font-title text-title-lg font-semibold text-text-primary"
                        >
                            Placa de Vídeo RTX 4070 Super Dual 12GB GDDR6X
                        </h3>
                        <p class="mt-0.5 text-body-sm text-text-muted">
                            Menor Preço Histórico Detectado Hoje
                        </p>

                        <div
                            class="mt-5 grid grid-cols-2 items-center gap-4 rounded-lg border border-surface-border bg-canvas/80 p-4"
                        >
                            <div>
                                <span
                                    class="block text-[11px] font-medium text-text-secondary"
                                >
                                    Preço à vista (PIX)
                                </span>
                                <div
                                    class="mt-0.5 font-price text-price-xl font-bold text-primary tabular-nums"
                                >
                                    R$ 3.899,00
                                </div>
                                <span
                                    class="mt-0.5 block text-[11px] text-text-muted"
                                >
                                    ou R$ 4.290,00 a prazo
                                </span>
                            </div>
                            <div class="border-l border-surface-border pl-4">
                                <span
                                    class="block text-[11px] font-medium text-text-secondary"
                                >
                                    Seu Preço Alvo
                                </span>
                                <div
                                    class="mt-0.5 font-price text-price-lg font-semibold text-amber tabular-nums"
                                >
                                    R$ 4.000,00
                                </div>
                                <span
                                    class="mt-0.5 block font-mono text-[11px] font-semibold text-primary"
                                >
                                    -R$ 101,00 abaixo
                                </span>
                            </div>
                        </div>

                        <div
                            class="mt-5 grid grid-cols-3 gap-3 border-t border-surface-border pt-4 text-center"
                        >
                            <div
                                class="rounded-lg bg-surface-elevated/60 p-2.5"
                            >
                                <TrendingDown
                                    class="mx-auto mb-1 size-4 text-primary"
                                />
                                <span
                                    class="block font-label text-[11px] text-text-secondary"
                                >
                                    Queda Real
                                </span>
                            </div>
                            <div
                                class="rounded-lg bg-surface-elevated/60 p-2.5"
                            >
                                <LineChart
                                    class="mx-auto mb-1 size-4 text-indigo"
                                />
                                <span
                                    class="block font-label text-[11px] text-text-secondary"
                                >
                                    7, 30 e 90d
                                </span>
                            </div>
                            <div
                                class="rounded-lg bg-surface-elevated/60 p-2.5"
                            >
                                <Bell class="mx-auto mb-1 size-4 text-amber" />
                                <span
                                    class="block font-label text-[11px] text-text-secondary"
                                >
                                    Alerta E-mail
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-surface-border bg-surface/50 py-6">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-6 text-body-sm text-text-muted sm:flex-row"
            >
                <div class="flex items-center gap-2">
                    <CheckCircle2 class="size-4 text-primary" />
                    <span>
                        Sistema de Monitoramento de Preços — Nocturne Emerald &
                        Warm Amber UI
                    </span>
                </div>
                <span>Monitoramento inteligente para o varejo brasileiro</span>
            </div>
        </footer>
    </div>
</template>
