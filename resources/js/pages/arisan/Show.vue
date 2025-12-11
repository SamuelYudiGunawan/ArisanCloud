<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { GroupDetail, MemberPaymentStatus, DrawHistory, Payment } from '@/types/arisan';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronLeft, Upload, Users, Wallet, Trophy, Info, DollarSign, CheckCircle, Clock, XCircle, UserPlus, Trash2, AlertCircle, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
    group: GroupDetail;
    userPayments: Payment[];
    canDraw: boolean;
}>();

// Flash messages
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
const errors = computed(() => page.props.errors as Record<string, string> | undefined);

const activeTab = ref<'payment' | 'winner' | 'history' | 'info'>('payment');

const tabs = [
    { id: 'payment', label: 'Payment Status', icon: Wallet },
    { id: 'winner', label: 'History Pemenang', icon: Trophy },
    { id: 'history', label: 'Pembayaran Bulanan', icon: DollarSign },
    { id: 'info', label: 'Group Info', icon: Info },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const getPeriodName = (periodNumber: number) => {
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const now = new Date();
    const monthIndex = (now.getMonth() - (props.group.total_periods - periodNumber)) % 12;
    const adjustedIndex = monthIndex < 0 ? monthIndex + 12 : monthIndex;
    return `${months[adjustedIndex]} ${now.getFullYear()}`;
};

// Payment form
const paymentForm = useForm({
    proof_image: null as File | null,
    notes: '',
});

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        selectedFile.value = target.files[0];
        paymentForm.proof_image = target.files[0];
    }
};

const submitPayment = () => {
    if (!selectedFile.value) return;
    
    paymentForm.post(`/arisan/${props.group.id}/pay`, {
        forceFormData: true,
        onSuccess: () => {
            paymentForm.reset();
            selectedFile.value = null;
        },
    });
};

const startPeriod = () => {
    router.post(`/arisan/${props.group.id}/start-period`);
};

// Draw animation state
const isDrawing = ref(false);
const drawAnimationNames = ref<string[]>([]);
const currentAnimationName = ref('');
const showWinnerDialog = ref(false);
const winnerName = ref('');
const winnerAmount = ref(0);

const performDraw = () => {
    if (!confirm('Apakah Anda yakin ingin melakukan undian? Pastikan semua member sudah membayar.')) {
        return;
    }

    // Get eligible members (those who haven't won yet in CURRENT cycle)
    const currentCycle = props.group.current_cycle || 1;
    const currentCycleWinnerIds = props.group.draw_history
        .filter(d => d.cycle_number === currentCycle)
        .map(d => d.winner.id);
    const eligibleMembers = props.group.members.filter(m => !currentCycleWinnerIds.includes(m.id));
    
    if (eligibleMembers.length === 0) {
        alert('Semua member sudah pernah menang!');
        return;
    }

    // Start the animation
    isDrawing.value = true;
    drawAnimationNames.value = eligibleMembers.map(m => m.name);
    
    // Shuffle animation - cycle through names rapidly then slow down
    let interval = 50;
    let iterations = 0;
    const maxIterations = 30;
    
    const animate = () => {
        const randomIndex = Math.floor(Math.random() * drawAnimationNames.value.length);
        currentAnimationName.value = drawAnimationNames.value[randomIndex];
        iterations++;
        
        if (iterations < maxIterations) {
            // Gradually slow down
            interval = 50 + (iterations * 15);
            setTimeout(animate, interval);
        } else {
            // Animation complete - now do the actual draw
            router.post(`/arisan/${props.group.id}/draw`, {}, {
                preserveScroll: true,
                onSuccess: (page: any) => {
                    isDrawing.value = false;
                    
                    // Get the winner from flash message or latest draw
                    const successMsg = page.props.flash?.success as string;
                    if (successMsg) {
                        // Extract winner name from message "Selamat! Pemenang undian: [name]..."
                        const match = successMsg.match(/Pemenang undian: ([^.]+)/);
                        if (match) {
                            winnerName.value = match[1].trim();
                            winnerAmount.value = props.group.contribution_amount * props.group.member_count;
                            currentAnimationName.value = winnerName.value;
                            
                            // Show winner dialog after a brief pause
                            setTimeout(() => {
                                showWinnerDialog.value = true;
                            }, 500);
                        }
                    }
                },
                onError: () => {
                    isDrawing.value = false;
                },
            });
        }
    };
    
    animate();
};

const closeWinnerDialog = () => {
    showWinnerDialog.value = false;
    winnerName.value = '';
};

const approvePayment = (paymentId: string) => {
    router.post(`/arisan/${props.group.id}/payments/${paymentId}/approve`, {}, {
        preserveScroll: true,
    });
};

const rejectPayment = (paymentId: string) => {
    router.post(`/arisan/${props.group.id}/payments/${paymentId}/reject`, {}, {
        preserveScroll: true,
    });
};

const totalPot = computed(() => props.group.contribution_amount * props.group.member_count);
const paidCount = computed(() => props.group.current_period_payment_status.filter(m => m.is_paid).length);
const unpaidCount = computed(() => props.group.member_count - paidCount.value);

// Check if current user has paid
const currentUserPaymentStatus = computed(() => {
    if (!props.group.active_period) return null;
    return props.group.current_period_payment_status.find(m => m.is_paid !== undefined);
});

const userPaidThisPeriod = computed(() => {
    return props.userPayments.some(p => 
        p.period_id === props.group.active_period?.id && 
        (p.status === 'approved' || p.status === 'pending')
    );
});

const userPaymentRejected = computed(() => {
    const payment = props.userPayments.find(p => 
        p.period_id === props.group.active_period?.id && 
        p.status === 'rejected'
    );
    return payment || null;
});

const canUploadPayment = computed(() => {
    if (!props.group.active_period) return false;
    // Can upload if not paid OR if previous payment was rejected
    return !userPaidThisPeriod.value;
});

const userPaidCount = computed(() => props.userPayments.filter(p => p.status === 'approved').length);

// Invite member form
const inviteForm = useForm({
    email: '',
});

const submitInvite = () => {
    inviteForm.post(`/arisan/${props.group.id}/invite`, {
        onSuccess: () => {
            inviteForm.reset();
        },
    });
};

const removeMember = (userId: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus member ini dari grup?')) {
        router.delete(`/arisan/${props.group.id}/members/${userId}`);
    }
};

// Check if can manage members (no active period AND arisan complete or not started)
const canManageMembers = computed(() => {
    const isCreator = props.group.is_creator;
    const noActivePeriod = !props.group.active_period;
    const arisanNotStarted = props.group.draw_history.length === 0;
    const arisanComplete = props.group.is_complete;
    
    return isCreator && noActivePeriod && (arisanNotStarted || arisanComplete);
});

// Payment proof dialog
const showProofDialog = ref(false);
const selectedPaymentProof = ref<{
    userName: string;
    proofImage: string | null;
    paymentId: string;
    notes: string | null;
} | null>(null);

const openProofDialog = (member: MemberPaymentStatus) => {
    selectedPaymentProof.value = {
        userName: member.user_name,
        proofImage: member.proof_image,
        paymentId: member.payment_id!,
        notes: null,
    };
    showProofDialog.value = true;
};

const closeProofDialog = () => {
    showProofDialog.value = false;
    selectedPaymentProof.value = null;
};

const approveFromDialog = () => {
    if (selectedPaymentProof.value) {
        const paymentId = selectedPaymentProof.value.paymentId;
        closeProofDialog();
        router.post(`/arisan/${props.group.id}/payments/${paymentId}/approve`, {}, {
            preserveScroll: true,
        });
    }
};

const rejectFromDialog = () => {
    if (selectedPaymentProof.value) {
        const paymentId = selectedPaymentProof.value.paymentId;
        closeProofDialog();
        router.post(`/arisan/${props.group.id}/payments/${paymentId}/reject`, {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="group.name" />

    <AppLayout :breadcrumbs="[
        { title: 'Home', href: '/arisan' },
        { title: group.name, href: `/arisan/${group.id}` },
    ]">
        <div class="p-6 min-h-screen" style="background-color: #F5F6F8;">
            <!-- Header -->
            <div class="mb-6">
                <Link href="/arisan" class="flex items-center text-[#1e3a5f] hover:text-[#152a45] mb-4">
                    <ChevronLeft class="w-4 h-4 mr-1" />
                    Kembali ke Home
                </Link>
            </div>

            <!-- Flash Messages -->
            <div v-if="flash?.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-2">
                <CheckCircle class="w-5 h-5" />
                {{ flash.success }}
            </div>
            <div v-if="flash?.error || (errors && Object.keys(errors).length > 0)" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <AlertCircle class="w-5 h-5" />
                    <span class="font-semibold">Error</span>
                </div>
                <p v-if="flash?.error">{{ flash.error }}</p>
                <ul v-if="errors" class="list-disc list-inside">
                    <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                </ul>
            </div>

            <Card class="bg-white border-gray-200">
                <CardContent class="pt-6">
                    <!-- Group Name -->
                    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ group.name }}</h1>

                    <!-- Tabs -->
                    <div class="flex flex-wrap gap-2 mb-6 border-b pb-4">
                        <Button
                            v-for="tab in tabs"
                            :key="tab.id"
                            :variant="activeTab === tab.id ? 'default' : 'outline'"
                            :class="activeTab === tab.id ? 'bg-[#1e3a5f] hover:bg-[#152a45] text-white' : 'text-gray-700 border-gray-300'"
                            @click="activeTab = tab.id as typeof activeTab"
                        >
                            <component :is="tab.icon" class="w-4 h-4 mr-2" />
                            {{ tab.label }}
                        </Button>
                    </div>

                    <!-- Tab Content: Payment Status -->
                    <div v-if="activeTab === 'payment'" class="space-y-6">
                        <!-- Transfer Info -->
                        <div class="text-center py-6 bg-gray-50 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2 text-gray-900">Transfer</h3>
                            <p class="text-gray-600 mb-3">Transfer ke penyelenggara arisan</p>
                            <div class="inline-block bg-[#1e3a5f] text-white px-4 py-2 rounded-lg">
                                {{ group.rekening_transfer || 'Belum diatur' }}
                            </div>
                            <p class="text-2xl font-bold mt-4 text-gray-900">{{ formatCurrency(group.contribution_amount) }}</p>
                        </div>

                        <!-- Start Period Button (Creator only, no active period) -->
                        <div v-if="group.is_creator && !group.active_period" class="text-center">
                            <Button @click="startPeriod" class="bg-green-500 hover:bg-green-600">
                                Mulai Periode Arisan
                            </Button>
                        </div>

                        <!-- Rejected Payment Warning -->
                        <div v-if="userPaymentRejected" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start gap-3">
                                <XCircle class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" />
                                <div>
                                    <h4 class="font-semibold text-red-700">Pembayaran Ditolak</h4>
                                    <p class="text-sm text-red-600 mt-1">
                                        Pembayaran Anda sebelumnya telah ditolak. Silakan upload bukti pembayaran yang baru.
                                    </p>
                                    <p v-if="userPaymentRejected.notes" class="text-sm text-red-600 mt-2">
                                        <span class="font-medium">Catatan:</span> {{ userPaymentRejected.notes }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Instructions & Upload -->
                        <div v-if="group.active_period && canUploadPayment" class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h4 class="font-semibold">Cara Pembayaran</h4>
                                <div class="space-y-3">
                                    <div class="flex items-start gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm">1</span>
                                        <div>
                                            <p class="font-medium">Buka aplikasi pembayaran</p>
                                            <p class="text-sm text-gray-500">Buka aplikasi e-wallet atau mobile banking Anda</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm">2</span>
                                        <div>
                                            <p class="font-medium">Pilih transfer ke bank</p>
                                            <p class="text-sm text-gray-500">Transfer ke {{ group.rekening_transfer || 'rekening penyelenggara' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm">3</span>
                                        <div>
                                            <p class="font-medium">Konfirmasi pembayaran</p>
                                            <p class="text-sm text-gray-500">Pastikan nominal {{ formatCurrency(group.contribution_amount) }} sudah benar</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="flex-shrink-0 w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-sm">✓</span>
                                        <div>
                                            <p class="font-medium">Pembayaran akan diverifikasi</p>
                                            <p class="text-sm text-gray-500">Penyelenggara arisan akan memverifikasi pembayaran Anda</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Proof -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h4 class="font-semibold mb-4 text-gray-900">Kirim Bukti Transaksi</h4>
                                <p class="text-sm text-gray-500 mb-4">BCA, Mandiri, BNI, BRI, dan bank lainnya</p>
                                
                                <form @submit.prevent="submitPayment" class="space-y-4">
                                    <div 
                                        class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-[#1e3a5f] transition-colors bg-white"
                                        @click="fileInput?.click()"
                                    >
                                        <input
                                            ref="fileInput"
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="handleFileChange"
                                        />
                                        <Upload class="w-12 h-12 mx-auto text-gray-400 mb-2" />
                                        <p v-if="selectedFile" class="text-sm text-green-600">{{ selectedFile.name }}</p>
                                        <p v-else class="text-sm text-gray-500">Masukan Bukti Transaksi Disini</p>
                                    </div>

                                    <Input
                                        v-model="paymentForm.notes"
                                        placeholder="Catatan (opsional)"
                                    />

                                    <Button 
                                        type="submit" 
                                        class="w-full bg-[#1e3a5f] hover:bg-[#152a45] text-white"
                                        :disabled="!selectedFile || paymentForm.processing"
                                    >
                                        <Upload class="w-4 h-4 mr-2" />
                                        Kirim Bukti Pembayaran
                                    </Button>
                                </form>
                            </div>
                        </div>

                        <!-- Already Paid Message -->
                        <div v-else-if="group.active_period && userPaidThisPeriod" class="text-center py-8 bg-green-50 rounded-lg">
                            <CheckCircle class="w-16 h-16 mx-auto text-green-500 mb-4" />
                            <h3 class="text-xl font-semibold text-green-700">Pembayaran Terkirim!</h3>
                            <p class="text-gray-600">
                                {{ props.userPayments.find(p => p.period_id === group.active_period?.id)?.status === 'approved' 
                                    ? 'Pembayaran Anda telah diverifikasi.' 
                                    : 'Menunggu verifikasi dari penyelenggara arisan.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Tab Content: Winner History -->
                    <div v-if="activeTab === 'winner'" class="space-y-6">
                        <h3 class="text-xl font-bold">History Pemenang</h3>
                        
                        <div v-if="group.draw_history.length > 0" class="space-y-4">
                            <!-- Show current cycle info if more than one cycle -->
                            <div v-if="(group.current_cycle || 1) > 1" class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="text-sm text-blue-700">
                                    <span class="font-semibold">Siklus {{ group.current_cycle }}</span> - 
                                    {{ group.current_cycle_winners || 0 }} dari {{ group.member_count }} member sudah menang
                                </p>
                            </div>
                            
                            <div 
                                v-for="draw in group.draw_history" 
                                :key="draw.id"
                                class="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-yellow-100 rounded-lg border border-yellow-200"
                            >
                                <div class="flex items-center gap-4">
                                    <Trophy class="w-8 h-8 text-yellow-500" />
                                    <div>
                                        <p class="font-semibold text-lg text-gray-900">{{ draw.winner.name }}</p>
                                        <p class="text-sm text-gray-600">
                                            <span v-if="(group.current_cycle || 1) > 1" class="text-blue-600">Siklus {{ draw.cycle_number }} - </span>
                                            Periode {{ draw.period_number }} - {{ formatDate(draw.draw_date) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-green-600">{{ formatCurrency(draw.total_pot_amount) }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-12 text-gray-500">
                            <Trophy class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <p>Belum ada pemenang. Tunggu undian pertama!</p>
                        </div>
                    </div>

                    <!-- Tab Content: Payment History -->
                    <div v-if="activeTab === 'history'" class="space-y-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold">Pembayaran Bulanan Saya</h3>
                            <Badge variant="outline" class="text-lg px-4 py-2">
                                Lunas {{ userPaidCount }} / {{ group.total_periods || userPayments.length }}
                            </Badge>
                        </div>

                        <div v-if="userPayments.length > 0" class="space-y-3">
                            <div 
                                v-for="payment in userPayments" 
                                :key="payment.id"
                                class="flex items-center justify-between p-4 border rounded-lg"
                            >
                                <div>
                                    <p class="font-medium">Periode {{ payment.period_number }}</p>
                                </div>
                                <div class="text-right flex items-center gap-4">
                                    <div>
                                        <Badge 
                                            :class="{
                                                'bg-green-500': payment.status === 'approved',
                                                'bg-yellow-500': payment.status === 'pending',
                                                'bg-red-500': payment.status === 'rejected',
                                            }"
                                        >
                                            {{ payment.status === 'approved' ? 'Lunas' : payment.status === 'pending' ? 'Proses' : 'Ditolak' }}
                                        </Badge>
                                        <p class="text-sm text-gray-500 mt-1">{{ formatDate(payment.payment_date) }}</p>
                                    </div>
                                    <Button 
                                        v-if="payment.status !== 'approved' && activeTab === 'history'"
                                        size="sm"
                                        class="bg-yellow-500 hover:bg-yellow-600"
                                        @click="activeTab = 'payment'"
                                    >
                                        <DollarSign class="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-12 text-gray-500">
                            <Wallet class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                            <p>Belum ada riwayat pembayaran.</p>
                        </div>
                    </div>

                    <!-- Tab Content: Group Info -->
                    <div v-if="activeTab === 'info'" class="space-y-6">
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Card class="bg-white border-gray-200">
                                <CardContent class="pt-6 text-center">
                                    <Users class="w-8 h-8 mx-auto text-blue-500 mb-2" />
                                    <p class="text-3xl font-bold text-gray-900">{{ group.member_count }}</p>
                                    <p class="text-gray-500">Group Member</p>
                                </CardContent>
                            </Card>
                            <Card class="bg-white border-gray-200">
                                <CardContent class="pt-6 text-center">
                                    <Wallet class="w-8 h-8 mx-auto text-green-500 mb-2" />
                                    <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(totalPot) }}</p>
                                    <p class="text-gray-500">Total Pot</p>
                                </CardContent>
                            </Card>
                            <Card class="bg-white border-gray-200">
                                <CardContent class="pt-6 text-center">
                                    <div class="flex items-center justify-center gap-2 mb-2">
                                        <span class="text-3xl font-bold text-yellow-500">{{ paidCount }}</span>
                                        <span class="text-xl text-gray-400">/{{ group.member_count }}</span>
                                    </div>
                                    <p class="text-gray-500">Pembayaran</p>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Invite Member Section (only when no active period) -->
                        <div v-if="canManageMembers" class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                <UserPlus class="w-5 h-5 text-blue-600" />
                                Tambah Anggota Baru
                            </h4>
                            <p class="text-sm text-gray-600 mb-3">
                                Anda dapat menambah atau menghapus anggota karena tidak ada periode aktif.
                            </p>
                            <form @submit.prevent="submitInvite" class="flex gap-2">
                                <Input
                                    v-model="inviteForm.email"
                                    type="email"
                                    placeholder="Email anggota baru"
                                    class="flex-1 bg-white"
                                    required
                                />
                                <Button 
                                    type="submit" 
                                    class="bg-green-500 hover:bg-green-600 text-white"
                                    :disabled="inviteForm.processing"
                                >
                                    <UserPlus class="w-4 h-4 mr-2" />
                                    Tambah
                                </Button>
                            </form>
                        </div>

                        <!-- Members List (when no active period) -->
                        <div v-if="!group.active_period" class="overflow-x-auto">
                            <h4 class="font-semibold text-gray-900 mb-3">Daftar Anggota</h4>
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Nama Anggota</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Email</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Tanggal Bergabung</th>
                                        <th v-if="canManageMembers" class="px-4 py-3 text-left text-sm font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="member in group.members" :key="member.id">
                                        <td class="px-4 py-3 text-gray-900">{{ member.name }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ member.email }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ formatDate(member.join_date) }}</td>
                                        <td v-if="canManageMembers" class="px-4 py-3">
                                            <Button 
                                                v-if="member.id !== group.creator.id"
                                                size="sm" 
                                                variant="destructive"
                                                @click="removeMember(member.id)"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                            <Badge v-else variant="outline" class="text-blue-600">Admin</Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Members Payment Status Table (when active period) -->
                        <div v-if="group.active_period" class="overflow-x-auto">
                            <h4 class="font-semibold text-gray-900 mb-3">Status Pembayaran Periode Ini</h4>
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Nama Anggota</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Status Pembayaran</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Tanggal Pembayaran</th>
                                        <th v-if="group.is_creator" class="px-4 py-3 text-left text-sm font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="member in group.current_period_payment_status" :key="member.user_id">
                                        <td class="px-4 py-3 text-gray-900">{{ member.user_name }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <span 
                                                    class="w-2 h-2 rounded-full"
                                                    :class="{
                                                        'bg-green-500': member.status === 'approved',
                                                        'bg-yellow-500': member.status === 'pending',
                                                        'bg-red-500': member.status === 'rejected' || member.status === 'not_paid',
                                                    }"
                                                ></span>
                                                <span :class="{
                                                    'text-green-600': member.status === 'approved',
                                                    'text-yellow-600': member.status === 'pending',
                                                    'text-red-600': member.status === 'rejected' || member.status === 'not_paid',
                                                }">
                                                    {{ member.status_label }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">
                                            {{ member.payment_date || '-' }}
                                        </td>
                                        <td v-if="group.is_creator" class="px-4 py-3">
                                            <Button 
                                                v-if="member.status === 'pending'"
                                                size="sm" 
                                                class="bg-blue-500 hover:bg-blue-600 text-white"
                                                @click="openProofDialog(member)"
                                            >
                                                Lihat Bukti
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Footer (when active period) -->
                        <div v-if="group.active_period" class="flex flex-wrap gap-4 justify-between items-center pt-4 border-t">
                            <div class="flex gap-4">
                                <div class="flex items-center gap-2 bg-green-100 px-4 py-2 rounded-lg">
                                    <Users class="w-5 h-5 text-green-600" />
                                    <span class="font-semibold text-gray-900">{{ paidCount }}</span>
                                    <span class="text-gray-600">Sudah Bayar</span>
                                </div>
                                <div class="flex items-center gap-2 bg-red-100 px-4 py-2 rounded-lg">
                                    <Users class="w-5 h-5 text-red-600" />
                                    <span class="font-semibold text-gray-900">{{ unpaidCount }}</span>
                                    <span class="text-gray-600">Belum Bayar</span>
                                </div>
                            </div>

                            <!-- Draw Button (Creator only) -->
                            <Button 
                                v-if="group.is_creator"
                                :disabled="!canDraw"
                                class="bg-yellow-500 hover:bg-yellow-600 text-gray-900"
                                @click="performDraw"
                            >
                                <Trophy class="w-4 h-4 mr-2" />
                                Mulai Undian
                            </Button>
                        </div>

                        <!-- Start Period Button (when no active period) -->
                        <div v-if="!group.active_period && group.is_creator" class="flex justify-center pt-4 border-t">
                            <Button 
                                @click="startPeriod" 
                                class="bg-green-500 hover:bg-green-600 text-white"
                            >
                                <CheckCircle class="w-4 h-4 mr-2" />
                                Mulai Periode Arisan
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Draw Animation Overlay -->
        <div 
            v-if="isDrawing" 
            class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center"
        >
            <div class="text-center">
                <div class="text-white/60 text-xl mb-4">🎰 Mengundi Pemenang...</div>
                <div class="bg-gradient-to-b from-yellow-400 via-yellow-500 to-yellow-600 rounded-2xl p-1 shadow-2xl">
                    <div class="bg-gray-900 rounded-xl px-12 py-8 min-w-[300px]">
                        <div class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-500 animate-pulse">
                            {{ currentAnimationName }}
                        </div>
                    </div>
                </div>
                <div class="flex justify-center gap-2 mt-6">
                    <span class="text-3xl animate-bounce">🎲</span>
                    <span class="text-3xl animate-bounce" style="animation-delay: 0.1s">🎯</span>
                    <span class="text-3xl animate-bounce" style="animation-delay: 0.2s">🎪</span>
                </div>
            </div>
        </div>

        <!-- Winner Celebration Dialog -->
        <Dialog :open="showWinnerDialog" @update:open="(val) => !val && closeWinnerDialog()">
            <DialogContent class="max-w-md text-center border-2 border-yellow-400">
                <div class="py-4">
                    <div class="flex justify-center mb-4">
                        <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center shadow-lg">
                            <Trophy class="w-10 h-10 text-white" />
                        </div>
                    </div>
                    <DialogHeader>
                        <DialogTitle class="text-2xl text-center">🏆 Selamat! 🏆</DialogTitle>
                        <DialogDescription class="text-center mt-2">Pemenang undian periode ini:</DialogDescription>
                    </DialogHeader>
                    <div class="my-4 text-3xl font-bold text-orange-600">{{ winnerName }}</div>
                    <div class="bg-green-100 rounded-lg px-4 py-3 mb-4">
                        <div class="text-sm text-green-600">Hadiah:</div>
                        <div class="text-xl font-bold text-green-700">Rp {{ winnerAmount.toLocaleString('id-ID') }}</div>
                    </div>
                    <Button @click="closeWinnerDialog" class="bg-yellow-500 hover:bg-yellow-600 text-white">
                        <CheckCircle class="w-4 h-4 mr-2" /> Lanjutkan
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Payment Proof Dialog -->
        <Dialog :open="showProofDialog" @update:open="(val) => !val && closeProofDialog()">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Bukti Pembayaran - {{ selectedPaymentProof?.userName }}</DialogTitle>
                    <DialogDescription>
                        Periksa bukti pembayaran sebelum menyetujui atau menolak.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="mt-4">
                    <div v-if="selectedPaymentProof?.proofImage" class="flex justify-center">
                        <img 
                            :src="selectedPaymentProof.proofImage" 
                            alt="Bukti Pembayaran"
                            class="max-h-96 rounded-lg border"
                        />
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        Tidak ada bukti pembayaran yang diupload.
                    </div>
                </div>

                <DialogFooter class="mt-6 flex gap-2">
                    <Button 
                        variant="outline" 
                        @click="closeProofDialog"
                    >
                        Tutup
                    </Button>
                    <Button 
                        variant="destructive"
                        @click="rejectFromDialog"
                    >
                        <XCircle class="w-4 h-4 mr-2" />
                        Tolak
                    </Button>
                    <Button 
                        class="bg-green-500 hover:bg-green-600 text-white"
                        @click="approveFromDialog"
                    >
                        <CheckCircle class="w-4 h-4 mr-2" />
                        Setujui
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

