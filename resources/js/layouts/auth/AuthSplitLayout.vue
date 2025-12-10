<script setup lang="ts">
import { Users } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh">
        <!-- Left Panel - Blue Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-[#1e3a5f] flex-col items-center justify-center p-12">
            <div class="text-center text-white">
                <!-- Logo -->
                <div class="flex items-center justify-center gap-4 mb-6">
                    <div class="bg-white rounded-xl p-3">
                        <Users class="w-10 h-10 text-[#1e3a5f]" />
                    </div>
                    <h1 class="text-4xl font-bold">Arisan</h1>
                </div>
                <p class="text-lg text-blue-200">
                    Kelola Arisan dengan Transparan & Modern
                </p>
            </div>
        </div>

        <!-- Right Panel - White Form -->
        <div class="flex-1 flex flex-col items-center justify-center p-6 md:p-12 bg-gray-100">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">
                <!-- Mobile Logo -->
                <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                    <div class="bg-[#1e3a5f] rounded-xl p-2">
                        <Users class="w-6 h-6 text-white" />
                    </div>
                    <h1 class="text-2xl font-bold text-[#1e3a5f]">Arisan</h1>
                </div>

                <!-- Title -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">{{ title }}</h2>
                    <p v-if="description" class="text-gray-500 mt-2">{{ description }}</p>
                </div>

                <!-- Form Slot -->
                <slot />
            </div>
        </div>
    </div>
</template>
