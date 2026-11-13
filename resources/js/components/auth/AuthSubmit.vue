<script setup lang="ts">
import Button from '@/components/ui/Button.vue'

defineProps<{ processing: boolean; label: string; processingLabel: string; tabindex?: number }>()
</script>

<template>
  <Button type="submit" size="lg" block class="mt-1" :tabindex="tabindex" :loading="processing">
    {{ processing ? processingLabel : label }}
  </Button>
</template>
