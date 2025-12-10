<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { ArisanGroup } from '@/types/arisan';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Users } from 'lucide-vue-next';

defineProps<{
    groups: ArisanGroup[];
    userPaymentStatus: Record<string, {
        status: 'not_paid' | 'pending' | 'approved' | 'rejected';
        status_label: string;
    }>;
}>();

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'approved':
            return 'bg-green-500 hover:bg-green-600 text-white';
        case 'pending':
            return 'bg-yellow-400 hover:bg-yellow-500 text-gray-900';
        case 'rejected':
            return 'bg-red-500 hover:bg-red-600 text-white';
        default:
            return 'bg-red-500 hover:bg-red-600 text-white';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'approved':
            return 'Sudah Bayar';
        case 'pending':
            return 'Proses Verifikasi';
        case 'rejected':
            return 'Ditolak';
        default:
            return 'Belum Lunas';
    }
};

const getPeriodLabel = (weeks: number) => {
    if (weeks === 1) return 'Mingguan';
    if (weeks === 2) return 'Dua Mingguan';
    if (weeks === 4) return 'Bulanan';
    return `${weeks} Minggu`;
};
</script>

<template>
    <Head title="Grup Arisan Saya" />

    <AppLayout :breadcrumbs="[{ title: 'Home', href: '/arisan' }]">
        <div class="p-6 min-h-screen" style="background-color: #F5F6F8;">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Arisan</h1>
                    <p class="text-gray-600 mt-1">Grup Arisan Saya</p>
                </div>
                <Link href="/arisan/create">
                    <Button class="bg-red-500 hover:bg-red-600 text-white">
                        <Plus class="w-4 h-4 mr-2" />
                        Buat Group
                    </Button>
                </Link>
            </div>

            <!-- Groups Grid -->
            <div v-if="groups.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card 
                    v-for="group in groups" 
                    :key="group.id"
                    class="cursor-pointer hover:shadow-lg transition-shadow bg-white border-gray-200"
                    @click="router.visit(`/arisan/${group.id}`)"
                >
                    <CardHeader class="pb-2">
                        <CardTitle class="text-lg">{{ group.name }}</CardTitle>
                        <p class="text-sm text-gray-500">
                            {{ getPeriodLabel(group.period_duration_weeks) }}
                            <span v-if="group.is_complete" class="text-green-600"> - Lunas</span>
                            <span v-else class="text-yellow-600"> - Belum Lunas</span>
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm text-gray-600">
                                <Users class="w-4 h-4 mr-2" />
                                {{ group.is_creator ? 'Admin' : 'Anggota' }}
                            </div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ formatCurrency(group.contribution_amount) }} / {{ getPeriodLabel(group.period_duration_weeks) }}
                            </p>
                        </div>
                        
                        <!-- Payment Status Button -->
                        <Button 
                            class="w-full mt-4"
                            :class="getStatusColor(userPaymentStatus[group.id]?.status || 'not_paid')"
                        >
                            {{ getStatusLabel(userPaymentStatus[group.id]?.status || 'not_paid') }}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <Users class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    Belum ada grup arisan
                </h3>
                <p class="text-gray-500 mb-4">
                    Buat grup arisan pertama Anda atau tunggu undangan dari teman.
                </p>
                <Link href="/arisan/create">
                    <Button class="bg-[#1e3a5f] hover:bg-[#152a45] text-white">
                        <Plus class="w-4 h-4 mr-2" />
                        Buat Group Arisan
                    </Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

