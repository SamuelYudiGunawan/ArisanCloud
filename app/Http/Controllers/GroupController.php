<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateGroupRequest;
use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Http\Resources\GroupDetailResource;
use App\Http\Resources\GroupResource;
use App\Models\ArisanGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * Get list of groups the authenticated user is a member of.
     */
    public function index(Request $request): JsonResponse
    {
        $groups = $request->user()
            ->arisanGroups()
            ->with('creator', 'members')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => GroupResource::collection($groups),
        ]);
    }

    /**
     * Create a new arisan group.
     */
    public function store(CreateGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // Create the group
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

            $group->load('creator', 'members');

            return response()->json([
                'success' => true,
                'message' => 'Grup arisan berhasil dibuat.',
                'data' => new GroupResource($group),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat grup arisan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific group.
     */
    public function show(Request $request, ArisanGroup $group): JsonResponse
    {
        // Check if user is a member
        if (!$group->isMember($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan anggota grup ini.',
            ], 403);
        }

        $group->load('creator', 'members', 'periods', 'draws.winner');

        return response()->json([
            'success' => true,
            'data' => new GroupDetailResource($group),
        ]);
    }

    /**
     * Update group information.
     */
    public function update(UpdateGroupRequest $request, ArisanGroup $group): JsonResponse
    {
        $validated = $request->validated();

        $group->update($validated);
        $group->load('creator', 'members');

        return response()->json([
            'success' => true,
            'message' => 'Grup arisan berhasil diperbarui.',
            'data' => new GroupResource($group),
        ]);
    }

    /**
     * Delete a group.
     */
    public function destroy(Request $request, ArisanGroup $group): JsonResponse
    {
        // Only creator can delete
        if (!$group->isCreator($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pembuat grup yang dapat menghapus grup ini.',
            ], 403);
        }

        // Check if arisan is complete
        if (!$group->isComplete() && $group->draws()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Grup tidak dapat dihapus karena arisan masih berjalan.',
            ], 422);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grup arisan berhasil dihapus.',
        ]);
    }

    /**
     * Invite a member to the group.
     */
    public function inviteMember(InviteMemberRequest $request, ArisanGroup $group): JsonResponse
    {
        $user = User::where('email', $request->validated()['email'])->first();

        $group->members()->attach($user->id, [
            'join_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil ditambahkan ke grup.',
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Remove a member from the group.
     */
    public function removeMember(Request $request, ArisanGroup $group, User $user): JsonResponse
    {
        // Only creator can remove members
        if (!$group->isCreator($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pembuat grup yang dapat menghapus anggota.',
            ], 403);
        }

        // Cannot remove the creator
        if ($group->isCreator($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Pembuat grup tidak dapat dihapus dari grup.',
            ], 422);
        }

        // Check if user is a member
        if (!$group->isMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'User bukan anggota grup ini.',
            ], 404);
        }

        $group->members()->detach($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil dihapus dari grup.',
        ]);
    }

    /**
     * Leave a group (for non-creator members).
     */
    public function leaveGroup(Request $request, ArisanGroup $group): JsonResponse
    {
        $user = $request->user();

        // Creator cannot leave their own group
        if ($group->isCreator($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Pembuat grup tidak dapat keluar dari grup sendiri.',
            ], 422);
        }

        // Check if user is a member
        if (!$group->isMember($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan anggota grup ini.',
            ], 404);
        }

        $group->members()->detach($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Anda berhasil keluar dari grup.',
        ]);
    }
}

