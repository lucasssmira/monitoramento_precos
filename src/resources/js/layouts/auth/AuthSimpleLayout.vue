<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Radar } from '@lucide/vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-canvas p-6 font-body text-text-primary md:p-10"
    >
        <div
            class="w-full max-w-md rounded-2xl border border-surface-border bg-surface p-6 shadow-2xl md:p-8"
        >
            <div class="flex flex-col gap-6">
                <div class="flex flex-col items-center gap-3">
                    <Link
                        :href="home()"
                        class="flex items-center gap-2.5 font-medium transition-opacity hover:opacity-90"
                    >
                        <div
                            class="relative flex size-10 items-center justify-center rounded-xl border border-primary/30 bg-primary-glow text-primary"
                        >
                            <Radar class="size-5 animate-pulse" />
                            <span
                                class="absolute -top-0.5 -right-0.5 size-2 rounded-full bg-primary ring-2 ring-surface"
                            />
                        </div>
                        <span
                            class="font-headline text-headline-md font-bold tracking-tight text-text-primary"
                        >
                            Monitor de Preços
                        </span>
                    </Link>
                    <div class="space-y-1.5 text-center">
                        <h1
                            class="font-headline text-title-lg font-bold text-text-primary"
                        >
                            {{ title }}
                        </h1>
                        <p class="text-center text-body-sm text-text-secondary">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
