<?php

namespace App\Http\Controllers;

use App\Http\Resources\DrawHistoryResource;
use App\Http\Resources\PeriodResource;
use App\Models\ArisanGroup;
use App\Models\ArisanPeriod;
use App\Models\DrawHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DrawController extends Controller
{
    /**
     * Start the first arisan period manually.
     */
    public function startPeriod(Request $request, ArisanGroup $group): JsonResponse
    {
        // Only creator can start period
        if (!$group->isCreator($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pembuat grup yang dapat memulai periode.',
            ], 403);
        }

        // Check if there's already an active period
        $activePeriod = $group->activePeriod();
        if ($activePeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada periode aktif. Selesaikan periode ini terlebih dahulu dengan melakukan draw.',
            ], 422);
        }

        // Check if there are enough members (minimum 2)
        if ($group->members()->count() < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal 2 anggota diperlukan untuk memulai arisan.',
            ], 422);
        }

        // If current cycle is complete, start a new cycle
        if ($group->isComplete()) {
            $group->startNewCycle();
            $group->refresh();
        }

        // Get the next period number
        $lastPeriod = $group->periods()->orderBy('period_number', 'desc')->first();
        $nextPeriodNumber = $lastPeriod ? $lastPeriod->period_number + 1 : 1;

        // Create new period
        $startDate = now()->toDateString();
        $endDate = now()->addWeeks($group->period_duration_weeks)->toDateString();

        $period = ArisanPeriod::create([
            'group_id' => $group->id,
            'period_number' => $nextPeriodNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
        ]);

        $cycleMessage = $group->current_cycle > 1 ? ' (Siklus ' . $group->current_cycle . ')' : '';
        return response()->json([
            'success' => true,
            'message' => 'Periode arisan berhasil dimulai.' . $cycleMessage,
            'data' => new PeriodResource($period),
        ], 201);
    }

    /**
     * Draw a winner for the current period.
     */
    public function draw(Request $request, ArisanGroup $group): JsonResponse
    {
        // Only creator can perform draw
        if (!$group->isCreator($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pembuat grup yang dapat melakukan undian.',
            ], 403);
        }

        // Get active period
        $activePeriod = $group->activePeriod();
        if (!$activePeriod) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada periode aktif. Mulai periode terlebih dahulu.',
            ], 422);
        }

        // Check if all members have paid
        if (!$activePeriod->allMembersPaid()) {
            $unpaidMembers = $activePeriod->unpaidMembers();
            $unpaidNames = $unpaidMembers->pluck('name')->join(', ');
            
            return response()->json([
                'success' => false,
                'message' => 'Draw tidak dapat dilakukan. Member berikut belum membayar: ' . $unpaidNames,
                'unpaid_members' => $unpaidMembers->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                ])->toArray(),
            ], 422);
        }

        // Get eligible members (those who haven't won yet)
        $previousWinners = $group->winners();
        $eligibleMembers = $group->members()
            ->whereNotIn('users.id', $previousWinners)
            ->get();

        if ($eligibleMembers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Semua member sudah pernah menang. Arisan telah selesai!',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Randomly select a winner
            $winner = $eligibleMembers->random();

            // Calculate total pot
            $totalPot = $group->contribution_amount * $group->members()->count();

            // Create draw record with cycle number
            $draw = DrawHistory::create([
                'group_id' => $group->id,
                'period_id' => $activePeriod->id,
                'winner_user_id' => $winner->id,
                'draw_date' => now(),
                'total_pot_amount' => $totalPot,
                'cycle_number' => $group->current_cycle,
            ]);

            // Complete the current period
            $activePeriod->update(['status' => 'completed']);

            // Check if current cycle is complete
            $group->refresh();
            $isComplete = $group->isComplete();

            DB::commit();

            $draw->load('winner', 'period');

            return response()->json([
                'success' => true,
                'message' => 'Draw berhasil! Selamat kepada pemenang: ' . $winner->name . '. Mulai periode baru untuk melanjutkan arisan.',
                'data' => [
                    'draw' => new DrawHistoryResource($draw),
                    'is_arisan_complete' => $isComplete,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan draw.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get draw history for a group.
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

        $draws = $group->draws()
            ->with('winner', 'period')
            ->orderBy('draw_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => DrawHistoryResource::collection($draws),
        ]);
    }
}

