<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NewsService;
use App\Services\GameAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct(
        private NewsService $newsService,
        private GameAccountService $gameService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'news' => $this->newsService->getAll()
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $news = $this->newsService->getById($id);
        
        if (!$news) {
            return response()->json(['error' => 'not found'], 404);
        }

        return response()->json(['news' => $news]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$this->canManageNews($request)) {
            return response()->json(['error' => '8+ only bro'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'message2' => 'required|string',
        ]);

        $user = $request->user();
        
        $id = $this->newsService->create(
            $request->title,
            $request->message,
            $request->message2,
            $user->game_account_name
        );

        return response()->json(['id' => $id, 'ok' => true]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->canManageNews($request)) {
            return response()->json(['error' => '8+ only bro'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'message2' => 'required|string',
        ]);

        $this->newsService->update(
            $id,
            $request->title,
            $request->message,
            $request->message2
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$this->canManageNews($request)) {
            return response()->json(['error' => '8+ only bro'], 403);
        }

        $this->newsService->delete($id);

        return response()->json(['ok' => true]);
    }

    private function canManageNews(Request $request): bool
    {
        $myLevel = $request->attributes->get('admin_level');
        $user = $request->user();
        $myAdmin = $this->gameService->getAdminByName($user->server, $user->game_account_name);
        $isGA = $myAdmin && ($myAdmin->GA ?? 0) == 1;

        return $myLevel === 8 && $isGA;
    }
}

