<script setup lang="ts">
import { SharedProps } from "@/types";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage<SharedProps>();
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <nav class="flex items-center gap-6 border-b bg-white px-6 py-3">
            <span class="font-semibold">{{ page.props.app.name }}</span>
            <Link
                href="/dashboard"
                class="text-sm text-gray-600 hover:text-gray-900"
                >Panel</Link
            >
            <span
                class="ml-auto rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-800"
            >
                driver: {{ page.props.app.smsDriver }}
            </span>
        </nav>
        <main class="p-6"><slot /></main>
    </div>
</template>
