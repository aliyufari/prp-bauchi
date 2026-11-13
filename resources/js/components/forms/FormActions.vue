<script setup lang="ts">
import Button from '@/components/ui/Button.vue'

withDefaults(defineProps<{ processing?: boolean; submitLabel?: string; processingLabel?: string }>(), {
  processing: false, submitLabel: 'Save', processingLabel: 'Saving…',
})
const emit = defineEmits<{ cancel: [] }>()
</script>

<template>
  <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-end">
    <Button variant="neutral" class="w-full sm:w-auto" :disabled="processing" @click="emit('cancel')">Cancel</Button>
    <Button type="submit" class="w-full sm:w-auto" :loading="processing">
      {{ processing ? processingLabel : submitLabel }}
    </Button>
  </div>
</template>
