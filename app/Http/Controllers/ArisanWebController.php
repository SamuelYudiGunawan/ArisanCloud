<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGroupRequest;
use App\Http\Requests\UploadPaymentRequest;
use App\Models\ArisanGroup;
use App\Models\ArisanPeriod;
use App\Models\DrawHistory;
use App\Models\PaymentHistory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ArisanWebController extends Controller
{
    /**
     * Display list of user's arisan groups.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        $groups = $user->arisanGroups()
            ->with('creator', 'members')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'period_duration_weeks' => $group->period_duration_weeks,
                    'contribution_amount' => $group->contribution_amount,
                    'creator' => [
                        'id' => $group->creator->id,
                        'name' => $group->creator->name,
                    ],
                    'member_count' => $group->members->count(),
                    'is_creator' => $group->creator_user_id === auth()->id(),
                    'is_complete' => $group->isComplete(),
                ];
            });

        // Get user's payment status for each group
        $userPaymentStatus = [];
        foreach ($groups as $group) {
            $arisanGroup = ArisanGroup::find($group['id']);
            $activePeriod = $arisanGroup?->activePeriod();
            
            if ($activePeriod) {
                $payment = PaymentHistory::where('group_id', $group['id'])
                    ->where('user_id', $user->id)
                    ->where('period_id', $activePeriod->id)
                    ->first();
                
                $userPaymentStatus[$group['id']] = [
                    'status' => $payment?->status ?? 'not_paid',
                    'status_label' => match($payment?->status) {
                        'approved' => 'Lunas',
                        'pending' => 'Proses Verifikasi',
                        'rejected' => 'Ditolak',
                        default => 'Belum Bayar',
                    },
                ];
            } else {
                $userPaymentStatus[$group['id']] = [
                    'status' => 'not_paid',
                    'status_label' => 'Belum Mulai',
                ];
            }
        }

        return Inertia::render('arisan/Index', [
            'groups' => $groups,
            'userPaymentStatus' => $userPaymentStatus,
        ]);
    }

    /**
     * Show create group form.
     */
    public function create(): Response
    {
        return Inertia::render('arisan/Create');
    }

    /**
     * Store a new group.
     */
    public function store(CreateGroupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $group = ArisanGroup::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'creator_user_id' => $request->user()->id,
                'rekening_transfer' => $validated['rekening_transfer'] ?? null,
                'period_duration_weeks' => $validated['period_duration_weeks'],
                'contribution_amount' => $validated['contribution_amount'],
            ]);

            // Add creator as a member
            $group->members()->attach($request->user()->id, [
                'join_date' => now(),
            ]);

            // Invite additional members if provided
            if (!empty($validated['member_emails'])) {
                $users = User::whereIn('email', $validated['member_emails'])
                    ->where('id', '!=', $request->user()->id)
                    ->get();

                foreach ($users as $user) {
                    $group->members()->attach($user->id, [
                        'join_date' => now(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('arisan.show', $group->id)
                ->with('success', 'Grup arisan berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal membuat grup: ' . $e->getMessage()]);
        }
    }

    /**
     * Show group details.
     */
    public function show(Request $request, ArisanGroup $group): Response
    {
        $user = $request->user();

        // Check if user is a member
        if (!$group->isMember($user)) {
            abort(403, 'Anda bukan anggota grup ini.');
        }

        // Refresh the group to get latest data
        $group = $group->fresh(['creator', 'members', 'periods', 'draws.winner', 'draws.period']);

        $activePeriod = $group->activePeriod();

        // Get current period payment status
        $currentPeriodPaymentStatus = $activePeriod 
            ? $activePeriod->memberPaymentStatus() 
            : [];

        // Get user's payment history for this group
        $userPayments = PaymentHistory::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->with('period')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'user' => [
                        'id' => $payment->user_id,
                        'name' => $payment->user->name,
                    ],
                    'period_id' => $payment->period_id,
                    'period_number' => $payment->period?->period_number,
                    'amount_paid' => $payment->amount_paid,
                    'payment_date' => $payment->payment_date->toISOString(),
                    'status' => $payment->status,
                    'proof_image' => $payment->proof_image ? asset('storage/' . $payment->proof_image) : null,
                    'notes' => $payment->notes,
                ];
            });

        // Check if draw can be performed
        // Refresh the period to get latest payment data
        if ($activePeriod) {
            $activePeriod = $activePeriod->fresh(['payments']);
        }
        
        $canDraw = $group->isCreator($user) 
            && $activePeriod 
            && $activePeriod->allMembersPaid()
            && !$group->isComplete();

        return Inertia::render('arisan/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'rekening_transfer' => $group->rekening_transfer,
                'period_duration_weeks' => $group->period_duration_weeks,
                'contribution_amount' => $group->contribution_amount,
                'creator' => [
                    'id' => $group->creator->id,
                    'name' => $group->creator->name,
                    'email' => $group->creator->email,
                ],
                'is_creator' => $group->creator_user_id === $user->id,
                'is_complete' => $group->isComplete(),
                'member_count' => $group->members->count(),
                'members' => $group->members->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'email' => $m->email,
                    'join_date' => $m->pivot->join_date,
                ]),
                'active_period' => $activePeriod ? [
                    'id' => $activePeriod->id,
                    'period_number' => $activePeriod->period_number,
                    'start_date' => $activePeriod->start_date->format('Y-m-d'),
                    'end_date' => $activePeriod->end_date->format('Y-m-d'),
                    'status' => $activePeriod->status,
                ] : null,
                'current_period_payment_status' => $currentPeriodPaymentStatus,
                'draw_history' => $group->draws->map(fn($d) => [
                    'id' => $d->id,
                    'period_id' => $d->period_id,
                    'period_number' => $d->period?->period_number,
                    'winner' => [
                        'id' => $d->winner->id,
                        'name' => $d->winner->name,
                    ],
                    'draw_date' => $d->draw_date->toISOString(),
                    'total_pot_amount' => $d->total_pot_amount,
                ]),
                'total_periods' => $group->periods->count(),
                'total_draws' => $group->draws->count(),
            ],
            'userPayments' => $userPayments,
            'canDraw' => $canDraw,
        ]);
    }

    /**
     * Invite a member to the group.
     */
    public function inviteMember(Request $request, ArisanGroup $group): RedirectResponse
    {
        // Only creator can invite
        if (!$group->isCreator($request->user())) {
            return back()->withErrors(['error' => 'Hanya pembuat grup yang dapat mengundang anggota.']);
        }

        // Validate email
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'User dengan email tersebut tidak ditemukan.',
        ]);

        // Check if arisan cycle is complete (all members have won) - only applies if arisan has started
        if (!$group->isComplete() && $group->draws()->count() > 0) {
            return back()->withErrors(['error' => 'Member hanya bisa ditambahkan jika semua member sudah pernah menang (siklus arisan selesai).']);
        }

        // Check if there's an active period
        $activePeriod = $group->activePeriod();
        if ($activePeriod) {
            return back()->withErrors(['error' => 'Member hanya bisa ditambahkan jika tidak ada periode aktif.']);
        }

        // Check if user is already a member
        $user = User::where('email', $validated['email'])->first();
        if ($group->isMember($user)) {
            return back()->withErrors(['error' => 'User sudah menjadi anggota grup ini.']);
        }

        $group->members()->attach($user->id, [
            'join_date' => now(),
        ]);

        return back()->with('success', 'Member ' . $user->name . ' berhasil ditambahkan!');
    }

    /**
     * Remove a member from the group.
     */
    public function removeMember(Request $request, ArisanGroup $group, User $user): RedirectResponse
    {
        // Only creator can remove
        if (!$group->isCreator($request->user())) {
            return back()->withErrors(['error' => 'Hanya pembuat grup yang dapat menghapus anggota.']);
        }

        // Cannot remove the creator
        if ($group->isCreator($user)) {
            return back()->withErrors(['error' => 'Pembuat grup tidak dapat dihapus dari grup.']);
        }

        // Check if user is a member
        if (!$group->isMember($user)) {
            return back()->withErrors(['error' => 'User bukan anggota grup ini.']);
        }

        // Check if there's an active period
        $activePeriod = $group->activePeriod();
        if ($activePeriod) {
            return back()->withErrors(['error' => 'Member hanya bisa dihapus jika tidak ada periode aktif.']);
        }

        $group->members()->detach($user->id);

        return back()->with('success', 'Member berhasil dihapus dari grup.');
    }

    /**
     * Start a new arisan period.
     */
    public function startPeriod(Request $request, ArisanGroup $group): RedirectResponse
    {
        if (!$group->isCreator($request->user())) {
            return back()->withErrors(['error' => 'Hanya pembuat grup yang dapat memulai periode.']);
        }

        $activePeriod = $group->activePeriod();
        if ($activePeriod) {
            return back()->withErrors(['error' => 'Sudah ada periode aktif.']);
        }

        if ($group->members()->count() < 2) {
            return back()->withErrors(['error' => 'Minimal 2 anggota diperlukan.']);
        }

        $lastPeriod = $group->periods()->orderBy('period_number', 'desc')->first();
        $nextPeriodNumber = $lastPeriod ? $lastPeriod->period_number + 1 : 1;

        ArisanPeriod::create([
            'group_id' => $group->id,
            'period_number' => $nextPeriodNumber,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeeks($group->period_duration_weeks)->toDateString(),
            'status' => 'active',
        ]);

        return back()->with('success', 'Periode arisan berhasil dimulai!');
    }

    /**
     * Upload payment proof.
     */
    public function pay(UploadPaymentRequest $request, ArisanGroup $group): RedirectResponse
    {
        $activePeriod = $group->activePeriod();

        if (!$activePeriod) {
            return back()->withErrors(['error' => 'Tidak ada periode aktif.']);
        }

        // Check if already paid
        $existingPayment = PaymentHistory::where('group_id', $group->id)
            ->where('user_id', $request->user()->id)
            ->where('period_id', $activePeriod->id)
            ->first();

        if ($existingPayment && $existingPayment->status !== 'rejected') {
            return back()->withErrors(['error' => 'Anda sudah melakukan pembayaran untuk periode ini.']);
        }

        // Delete old rejected payment if exists
        if ($existingPayment && $existingPayment->status === 'rejected') {
            if ($existingPayment->proof_image) {
                Storage::disk('public')->delete($existingPayment->proof_image);
            }
            $existingPayment->delete();
        }

        $proofPath = $request->file('proof_image')->store('payment-proofs', 'public');

        PaymentHistory::create([
            'group_id' => $group->id,
            'user_id' => $request->user()->id,
            'period_id' => $activePeriod->id,
            'amount_paid' => $group->contribution_amount,
            'payment_date' => now(),
            'status' => 'pending',
            'proof_image' => $proofPath,
            'notes' => $request->validated()['notes'] ?? null,
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil diupload!');
    }

    /**
     * Approve a payment.
     */
    public function approvePayment(Request $request, ArisanGroup $group, PaymentHistory $payment): RedirectResponse
    {
        if (!$group->isCreator($request->user())) {
            return back()->withErrors(['error' => 'Hanya pembuat grup yang dapat menyetujui pembayaran.']);
        }

        if ($payment->group_id !== $group->id) {
            return back()->withErrors(['error' => 'Pembayaran tidak ditemukan.']);
        }

        if ($payment->status !== 'pending') {
            return back()->withErrors(['error' => 'Pembayaran sudah diproses.']);
        }

        $payment->update(['status' => 'approved']);

        return back()->with('success', 'Pembayaran berhasil disetujui!');
    }

    /**
     * Reject a payment.
     */
    public function rejectPayment(Request $request, ArisanGroup $group, PaymentHistory $payment): RedirectResponse
    {
        if (!$group->isCreator($request->user())) {
            return back()->withErrors(['error' => 'Hanya pembuat grup yang dapat menolak pembayaran.']);
        }

        if ($payment->group_id !== $group->id) {
            return back()->withErrors(['error' => 'Pembayaran tidak ditemukan.']);
        }

        if ($payment->status !== 'pending') {
            return back()->withErrors(['error' => 'Pembayaran sudah diproses.']);
        }

        $payment->update(['status' => 'rejected']);

        return back()->with('success', 'Pembayaran ditolak.');
    }

    /**
     * Perform draw.
     */
    public function draw(Request $request, ArisanGroup $group): RedirectResponse
    {
        if (!$group->isCreator($request->user())) {
            return back()->withErrors(['error' => 'Hanya pembuat grup yang dapat melakukan undian.']);
        }

        $activePeriod = $group->activePeriod();
        if (!$activePeriod) {
            return back()->withErrors(['error' => 'Tidak ada periode aktif.']);
        }

        if (!$activePeriod->allMembersPaid()) {
            return back()->withErrors(['error' => 'Semua member harus sudah membayar sebelum undian.']);
        }

        // Get eligible members (haven't won yet)
        $previousWinners = $group->winners();
        $eligibleMembers = $group->members()
            ->whereNotIn('users.id', $previousWinners)
            ->get();

        if ($eligibleMembers->isEmpty()) {
            return back()->withErrors(['error' => 'Semua member sudah pernah menang. Arisan selesai!']);
        }

        try {
            DB::beginTransaction();

            $winner = $eligibleMembers->random();
            $totalPot = $group->contribution_amount * $group->members()->count();

            DrawHistory::create([
                'group_id' => $group->id,
                'period_id' => $activePeriod->id,
                'winner_user_id' => $winner->id,
                'draw_date' => now(),
                'total_pot_amount' => $totalPot,
            ]);

            $activePeriod->update(['status' => 'completed']);

            // Check if arisan is complete
            $isComplete = $group->members()->count() === ($group->draws()->count() + 1);

            DB::commit();

            $message = 'Selamat! Pemenang undian: ' . $winner->name;
            if (!$isComplete) {
                $message .= '. Mulai periode baru untuk melanjutkan arisan.';
            } else {
                $message .= '. Arisan telah selesai!';
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal melakukan undian: ' . $e->getMessage()]);
        }
    }
}

