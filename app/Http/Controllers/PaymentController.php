<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\ArisanGroup;
use App\Models\PaymentHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Upload payment proof for the current period.
     */
    public function store(UploadPaymentRequest $request, ArisanGroup $group): JsonResponse
    {
        $activePeriod = $group->activePeriod();

        if (!$activePeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada periode aktif saat ini.',
            ], 422);
        }

        // Store the proof image
        $proofPath = $request->file('proof_image')->store('payment-proofs', 'public');

        $payment = PaymentHistory::create([
            'group_id' => $group->id,
            'user_id' => $request->user()->id,
            'period_id' => $activePeriod->id,
            'amount_paid' => $group->contribution_amount,
            'payment_date' => now(),
            'status' => 'pending',
            'proof_image' => $proofPath,
            'notes' => $request->validated()['notes'] ?? null,
        ]);

        $payment->load('user', 'period');

        return response()->json([
            'success' => true,
            'message' => 'Bukti pembayaran berhasil diupload. Menunggu persetujuan dari admin grup.',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    /**
     * Approve a payment.
     */
    public function approve(Request $request, ArisanGroup $group, PaymentHistory $payment): JsonResponse
    {
        // Only creator can approve
        if (!$group->isCreator($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pembuat grup yang dapat menyetujui pembayaran.',
            ], 403);
        }

        // Check if payment belongs to this group
        if ($payment->group_id !== $group->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran tidak ditemukan dalam grup ini.',
            ], 404);
        }

        // Check if already processed
        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran sudah diproses sebelumnya.',
            ], 422);
        }

        $payment->update(['status' => 'approved']);
        $payment->load('user', 'period');

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil disetujui.',
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Reject a payment.
     */
    public function reject(Request $request, ArisanGroup $group, PaymentHistory $payment): JsonResponse
    {
        // Only creator can reject
        if (!$group->isCreator($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pembuat grup yang dapat menolak pembayaran.',
            ], 403);
        }

        // Check if payment belongs to this group
        if ($payment->group_id !== $group->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran tidak ditemukan dalam grup ini.',
            ], 404);
        }

        // Check if already processed
        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran sudah diproses sebelumnya.',
            ], 422);
        }

        $payment->update(['status' => 'rejected']);

        // Delete the proof image
        if ($payment->proof_image) {
            Storage::disk('public')->delete($payment->proof_image);
        }

        $payment->load('user', 'period');

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran ditolak. Member dapat mengupload ulang bukti pembayaran.',
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * Get payment history for a group.
     */
    public function history(Request $request, ArisanGroup $group): JsonResponse
    {
        // Check if user is a member
        if (!$group->isMember($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan anggota grup ini.',
            ], 403);
        }

        $payments = $group->payments()
            ->with('user', 'period')
            ->orderBy('payment_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
        ]);
    }

    /**
     * Get current period payment status for all members.
     */
    public function currentPeriodStatus(Request $request, ArisanGroup $group): JsonResponse
    {
        // Check if user is a member
        if (!$group->isMember($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan anggota grup ini.',
            ], 403);
        }

        $activePeriod = $group->activePeriod();

        if (!$activePeriod) {
            return response()->json([
                'success' => true,
                'data' => [
                    'period' => null,
                    'members' => [],
                    'summary' => [
                        'total_members' => $group->members()->count(),
                        'paid_count' => 0,
                        'unpaid_count' => $group->members()->count(),
                    ],
                ],
            ]);
        }

        $memberStatus = $activePeriod->memberPaymentStatus();
        $paidCount = collect($memberStatus)->where('is_paid', true)->count();
        $totalMembers = count($memberStatus);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'id' => $activePeriod->id,
                    'period_number' => $activePeriod->period_number,
                    'start_date' => $activePeriod->start_date->format('d/m/Y'),
                    'end_date' => $activePeriod->end_date->format('d/m/Y'),
                    'status' => $activePeriod->status,
                ],
                'members' => $memberStatus,
                'summary' => [
                    'total_members' => $totalMembers,
                    'paid_count' => $paidCount,
                    'unpaid_count' => $totalMembers - $paidCount,
                    'total_pot' => $group->contribution_amount * $totalMembers,
                    'collected' => $group->contribution_amount * $paidCount,
                ],
            ],
        ]);
    }
}

