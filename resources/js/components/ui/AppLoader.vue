<script setup lang="ts">
import { Loader } from 'lucide-vue-next'
</script>

<template>
    <div
        class="fixed inset-0 z-[9999] flex h-screen w-screen items-center justify-center bg-background/95 text-primary backdrop-blur-[2px]"
        role="status"
        aria-live="polite"
        aria-label="Loading page"
    >
        <span class="flex h-24 w-24 items-center justify-center rounded-full">
            <Loader :size="60" :stroke-width="1.8" class="animate-spin" />
        </span>
    </div>
</template>
