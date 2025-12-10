<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ChevronLeft, Plus, Minus, X } from 'lucide-vue-next';
import { ref } from 'vue';

const form = useForm({
    name: '',
    description: '',
    contribution_amount: '',
    period_duration_weeks: '4',
    rekening_transfer: '',
    member_emails: [] as string[],
});

const newMemberEmail = ref('');
const memberEmails = ref<string[]>([]);

const addMember = () => {
    if (newMemberEmail.value && !memberEmails.value.includes(newMemberEmail.value)) {
        memberEmails.value.push(newMemberEmail.value);
        newMemberEmail.value = '';
    }
};

const removeMember = (index: number) => {
    memberEmails.value.splice(index, 1);
};

const submit = () => {
    form.member_emails = memberEmails.value;
    form.post('/arisan', {
        onSuccess: () => {
            form.reset();
            memberEmails.value = [];
        },
    });
};
</script>

<template>
    <Head title="Buat Grup Arisan" />

    <AppLayout :breadcrumbs="[
        { title: 'Home', href: '/arisan' },
        { title: 'Buat Grup', href: '/arisan/create' },
    ]">
        <div class="p-6 max-w-3xl mx-auto min-h-screen" style="background-color: #F5F6F8;">
            <!-- Header -->
            <div class="mb-6">
                <Link href="/arisan" class="flex items-center text-[#1e3a5f] hover:text-[#152a45] mb-4">
                    <ChevronLeft class="w-4 h-4 mr-1" />
                    Kembali ke Home
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">Buat Grup Arisan</h1>
            </div>

            <Card class="bg-white border-gray-200">
                <CardContent class="pt-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Group Name -->
                        <div class="space-y-2">
                            <Label for="name">Nama Group</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Masukkan nama grup"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Description -->
                        <div class="space-y-2">
                            <Label for="description">Deskripsi Group</Label>
                            <Input
                                id="description"
                                v-model="form.description"
                                type="text"
                                placeholder="Deskripsi singkat grup arisan"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <!-- Rekening Transfer -->
                        <div class="space-y-2">
                            <Label for="rekening">Rekening Transfer</Label>
                            <Input
                                id="rekening"
                                v-model="form.rekening_transfer"
                                type="text"
                                placeholder="Contoh: BCA 1234567890 a.n. John Doe"
                            />
                            <InputError :message="form.errors.rekening_transfer" />
                        </div>

                        <!-- Financial Config -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="amount">Jumlah Iuran</Label>
                                <Input
                                    id="amount"
                                    v-model="form.contribution_amount"
                                    type="number"
                                    placeholder="50000"
                                    min="1000"
                                    required
                                />
                                <InputError :message="form.errors.contribution_amount" />
                            </div>

                            <div class="space-y-2">
                                <Label for="period">Durasi Periode</Label>
                                <div class="flex items-center gap-2">
                                    <Input
                                        id="period"
                                        v-model="form.period_duration_weeks"
                                        type="number"
                                        min="1"
                                        max="52"
                                        class="w-20"
                                        required
                                    />
                                    <span class="text-sm text-gray-600">minggu</span>
                                </div>
                                <InputError :message="form.errors.period_duration_weeks" />
                            </div>
                        </div>

                        <!-- Member Invites -->
                        <div class="space-y-4">
                            <Label>Tambah Anggota</Label>
                            <p class="text-sm text-gray-500">Email anggota (harus sudah terdaftar)</p>
                            
                            <div class="flex gap-2">
                                <Input
                                    v-model="newMemberEmail"
                                    type="email"
                                    placeholder="email@example.com"
                                    @keyup.enter.prevent="addMember"
                                />
                                <Button type="button" @click="addMember" class="bg-green-500 hover:bg-green-600">
                                    <Plus class="w-4 h-4 mr-1" />
                                    Tambah Anggota
                                </Button>
                            </div>

                            <!-- Member List -->
                            <div v-if="memberEmails.length > 0" class="space-y-2">
                                <div 
                                    v-for="(email, index) in memberEmails" 
                                    :key="index"
                                    class="flex items-center justify-between bg-gray-50 p-3 rounded-lg"
                                >
                                    <span class="text-sm">{{ email }}</span>
                                    <Button 
                                        type="button" 
                                        variant="destructive" 
                                        size="sm"
                                        @click="removeMember(index)"
                                    >
                                        <Minus class="w-4 h-4 mr-1" />
                                        Hapus Anggota
                                    </Button>
                                </div>
                            </div>
                            <InputError :message="form.errors.member_emails" />
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end pt-4">
                            <Button 
                                type="submit" 
                                :disabled="form.processing"
                                class="bg-blue-600 hover:bg-blue-700"
                            >
                                <Plus class="w-4 h-4 mr-2" />
                                Buat Group
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

