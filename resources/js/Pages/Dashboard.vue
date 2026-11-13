<script setup lang="ts">
import AppLayout from "@/Layouts/AppLayout.vue";

defineOptions({ layout: AppLayout });
defineProps<{ stats: { sent: number; delivered: number; failed: number } }>();
</script>

<template>
    <h1 class="mb-4 text-xl font-semibold">Gönderim Özeti</h1>
    <div class="grid grid-cols-3 gap-4">
        <div
            v-for="(value, label) in stats"
            :key="label"
            class="rounded-lg bg-white p-4 shadow-sm"
        >
            <div class="text-sm text-gray-500">{{ label }}</div>
            <div class="text-2xl font-semibold">{{ value }}</div>
        </div>
    </div>
</template>
