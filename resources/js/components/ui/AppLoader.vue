<script setup lang="ts">
import { Loader } from 'lucide-vue-next'
</script>

<template>
    <div
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/80 backdrop-blur-[2px]"
        role="status"
        aria-live="polite"
        aria-label="Loading page"
    >
        <Loader :size="48" :stroke-width="1.8" class="animate-spin text-green-700" />
        <span class="sr-only">Loading page…</span>
    </div>
</template>
