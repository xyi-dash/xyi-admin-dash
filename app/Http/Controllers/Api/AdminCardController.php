<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ResolvesServer;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewAdminCardRequest;
use App\Http\Requests\StoreAdminCardRequest;
use App\Services\AdminCardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCardController extends Controller
{
    use ResolvesServer;

    public function __construct(
        private AdminCardService $cardService
    ) {}

    /**
     * Create a new admin card (Level 6+ access)
     */
    public function store(StoreAdminCardRequest $request): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);

        if (! $server) {
            return response()->json(['error' => 'boundary_of_fantasy_and_reality_blocked'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);

        if ($myLevel < 6) {
            return response()->json(['error' => 'insufficient_level', 'required_level' => 6], 403);
        }

        $card = $this->cardService->createCard(
            $request->validated(),
            $user->game_account_id,
            $user->game_account_name,
            $server
        );

        return response()->json([
            'success' => true,
            'card' => $card,
        ], 201);
    }

    /**
     * Get list of pending cards (Level 7+ access)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);

        if (! $server) {
            return response()->json(['error' => 'boundary_of_fantasy_and_reality_blocked'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);

        if ($myLevel < 7) {
            return response()->json(['error' => 'insufficient_level', 'required_level' => 7], 403);
        }

        $cards = $this->cardService->getPendingCards($server);

        return response()->json([
            'cards' => $cards,
        ]);
    }

    /**
     * Get list of pending warning cards (Level 7+ access)
     */
    public function pendingWarnings(Request $request): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);

        if (! $server) {
            return response()->json(['error' => 'boundary_of_fantasy_and_reality_blocked'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);

        if ($myLevel < 7) {
            return response()->json(['error' => 'insufficient_level', 'required_level' => 7], 403);
        }

        $cards = $this->cardService->getPendingCardsByType('warnings', $server);

        return response()->json([
            'cards' => $cards,
        ]);
    }

    /**
     * Get list of pending ban cards (Level 7+ access)
     */
    public function pendingBans(Request $request): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);

        if (! $server) {
            return response()->json(['error' => 'boundary_of_fantasy_and_reality_blocked'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);

        if ($myLevel < 7) {
            return response()->json(['error' => 'insufficient_level', 'required_level' => 7], 403);
        }

        $cards = $this->cardService->getPendingCardsByType('bans', $server);

        return response()->json([
            'cards' => $cards,
        ]);
    }

    /**
     * Get card history with filtering and sorting (Level 7+ access)
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);

        if (! $server) {
            return response()->json(['error' => 'boundary_of_fantasy_and_reality_blocked'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);

        if ($myLevel < 7) {
            return response()->json(['error' => 'insufficient_level', 'required_level' => 7], 403);
        }

        $filters = [
            'status' => $request->query('status'),
            'action_type' => $request->query('action_type'),
            'target_name' => $request->query('target_name'),
        ];

        $sort = $request->query('sort', 'desc');
        $perPage = min((int) $request->query('per_page', 20), 100);

        $result = $this->cardService->getProcessedCards($filters, $sort, $perPage);

        return response()->json([
            'cards' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
            ],
        ]);
    }

    /**
     * Review a card (approve/reject) (Level 7+ access)
     */
    public function review(ReviewAdminCardRequest $request, int $cardId): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);

        if (! $server) {
            return response()->json(['error' => 'boundary_of_fantasy_and_reality_blocked'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);

        if ($myLevel < 7) {
            return response()->json(['error' => 'insufficient_level', 'required_level' => 7], 403);
        }

        $result = $this->cardService->reviewCard(
            $cardId,
            $request->action,
            $user->game_account_id,
            $user->game_account_name,
            $server,
            $request->ip()
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], $result['status'] ?? 400);
        }

        return response()->json([
            'success' => true,
            'status' => $result['status'],
            'requires_confirmation' => $result['requires_confirmation'] ?? false,
            'action_executed' => $result['action_executed'] ?? false,
        ]);
    }

    /**
     * Confirm permanent ban (Level 7+ access)
     */
    public function confirmBan(Request $request, int $cardId): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);

        if (! $server) {
            return response()->json(['error' => 'boundary_of_fantasy_and_reality_blocked'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);

        if ($myLevel < 7) {
            return response()->json(['error' => 'insufficient_level', 'required_level' => 7], 403);
        }

        $result = $this->cardService->confirmPermanentBan(
            $cardId,
            $user->game_account_id,
            $user->game_account_name,
            $server,
            $request->ip()
        );

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], $result['status'] ?? 400);
        }

        return response()->json([
            'success' => true,
            'ban_applied' => true,
            'target' => $result['target'],
        ]);
    }
}
