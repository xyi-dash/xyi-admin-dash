<?php

namespace App\Services;

use App\Models\AdminBan;
use App\Models\AdminCard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * card-based approval system for admin actions
 * because apparently we can't trust level 6 admins to do anything
 * without a level 7+ signing off on it. bureaucracy at its finest!
 */
class AdminCardService
{
    private const SERVER_CONNECTIONS = [
        'one' => 'gangwar',
        'two' => 'gangwar2',
        'three' => 'gangwar3',
    ];

    public function __construct(
        private ActionLogService $actionLog,
        private GameAccountService $gameService
    ) {}

    /**
     * create a new admin card
     */
    public function createCard(array $data, int $creatorId, string $creatorName, string $creatorServer): AdminCard
    {
        $card = AdminCard::create([
            'creator_id' => $creatorId,
            'creator_name' => $creatorName,
            'creator_server' => $creatorServer,
            'target_admin_name' => $data['target_admin_name'],
            'action_type' => $data['action_type'],
            'reason' => $data['reason'],
            'evidence' => $data['evidence'] ?? null,
            'status' => 'pending',
        ]);

        $this->actionLog->log(
            'admin_card_created',
            $creatorId,
            $creatorName,
            $creatorServer,
            null,
            $data['target_admin_name'],
            $creatorServer,
            [
                'card_id' => $card->id,
                'action_type' => $data['action_type'],
                'reason' => $data['reason'],
            ],
            request()->ip()
        );

        Log::info('Admin card created', [
            'card_id' => $card->id,
            'creator' => $creatorName,
            'target' => $data['target_admin_name'],
            'action' => $data['action_type'],
        ]);

        return $card;
    }

    /**
     * get all pending cards, optionally filtered by server
     */
    public function getPendingCards(?string $server = null): Collection
    {
        $query = AdminCard::pending()->orderByDesc('created_at');

        if ($server) {
            $query->byServer($server);
        }

        return $query->get();
    }

    /**
     * get pending cards filtered by action type
     *
     * @param  string  $type  'warnings' or 'bans'
     * @param  string|null  $server  Optional server filter
     * @return Collection
     */
    public function getPendingCardsByType(string $type, ?string $server = null): Collection
    {
        $query = AdminCard::pending()->orderByDesc('created_at');

        if ($type === 'warnings') {
            $query->whereIn('action_type', ['warning_add', 'warning_remove', 'level_up', 'level_down']);
        } elseif ($type === 'bans') {
            $query->where('action_type', 'permanent_ban');
        }

        if ($server) {
            $query->byServer($server);
        }

        return $query->get();
    }

    /**
     * get processed cards with filtering and sorting
     *
     * @param  array  $filters  ['status', 'action_type', 'target_name']
     * @param  string  $sort  'asc' or 'desc'
     * @param  int  $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProcessedCards(array $filters = [], string $sort = 'desc', int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AdminCard::query()
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('reviewed_at', $sort);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (! empty($filters['target_name'])) {
            $query->where('target_admin_name', 'LIKE', '%'.$filters['target_name'].'%');
        }

        return $query->paginate($perPage);
    }

    /**
     * review a card (approve or reject)
     */
    public function reviewCard(int $cardId, string $action, int $reviewerId, string $reviewerName, string $reviewerServer): array
    {
        $card = AdminCard::find($cardId);

        if (! $card) {
            return ['success' => false, 'error' => 'card_not_found'];
        }

        if ($card->isProcessed()) {
            return ['success' => false, 'error' => 'card_already_processed'];
        }

        if ($action === 'reject') {
            return $this->rejectCard($card, $reviewerId, $reviewerName, $reviewerServer);
        }

        if ($action === 'approve') {
            return $this->approveCard($card, $reviewerId, $reviewerName, $reviewerServer);
        }

        return ['success' => false, 'error' => 'invalid_action'];
    }

    /**
     * reject a card
     */
    private function rejectCard(AdminCard $card, int $reviewerId, string $reviewerName, string $reviewerServer): array
    {
        $card->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        $this->actionLog->log(
            'admin_card_rejected',
            $reviewerId,
            $reviewerName,
            $reviewerServer,
            null,
            $card->target_admin_name,
            $card->creator_server,
            [
                'card_id' => $card->id,
                'action_type' => $card->action_type,
                'creator' => $card->creator_name,
            ],
            request()->ip()
        );

        Log::info('Admin card rejected', [
            'card_id' => $card->id,
            'reviewer' => $reviewerName,
            'target' => $card->target_admin_name,
        ]);

        return ['success' => true, 'status' => 'rejected'];
    }

    /**
     * approve a card and execute the action
     */
    private function approveCard(AdminCard $card, int $reviewerId, string $reviewerName, string $reviewerServer): array
    {
        // permanent ban does NOT auto-execute - just approve the card
        // main administration will handle the actual ban in-game
        if ($card->isPermanentBan()) {
            $card->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            $this->actionLog->log(
                'admin_card_approved',
                $reviewerId,
                $reviewerName,
                $reviewerServer,
                null,
                $card->target_admin_name,
                $card->creator_server,
                [
                    'card_id' => $card->id,
                    'action_type' => $card->action_type,
                    'note' => 'permanent_ban approved - main administration will execute in-game',
                ],
                request()->ip()
            );

            Log::info('Permanent ban card approved - no auto-execution', [
                'card_id' => $card->id,
                'reviewer' => $reviewerName,
                'target' => $card->target_admin_name,
            ]);

            return ['success' => true, 'status' => 'approved', 'action_executed' => false];
        }

        // execute the action for warning_add, warning_remove, level_up, level_down
        $result = $this->executeCardAction($card, $reviewerId, $reviewerName, $reviewerServer);

        if (! $result['success']) {
            return $result;
        }

        $card->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        $this->actionLog->log(
            'admin_card_approved',
            $reviewerId,
            $reviewerName,
            $reviewerServer,
            null,
            $card->target_admin_name,
            $card->creator_server,
            [
                'card_id' => $card->id,
                'action_type' => $card->action_type,
                'action_executed' => true,
            ],
            request()->ip()
        );

        Log::info('Admin card approved and action executed', [
            'card_id' => $card->id,
            'reviewer' => $reviewerName,
            'target' => $card->target_admin_name,
            'action' => $card->action_type,
        ]);

        return ['success' => true, 'status' => 'approved', 'action_executed' => true];
    }

    /**
     * execute the card action (warning_add, warning_remove, level_up, level_down)
     */
    public function executeCardAction(AdminCard $card, int $actorId, string $actorName, string $actorServer): array
    {
        return match ($card->action_type) {
            'warning_add' => $this->addWarningToAdmin($card, $actorId, $actorName, $actorServer),
            'warning_remove' => $this->removeWarningFromAdmin($card, $actorId, $actorName, $actorServer),
            'level_up' => $this->levelUpAdmin($card, $actorId, $actorName, $actorServer),
            'level_down' => $this->levelDownAdmin($card, $actorId, $actorName, $actorServer),
            'permanent_ban' => ['success' => false, 'error' => 'permanent_ban_no_auto_execution'],
            default => ['success' => false, 'error' => 'invalid_action_type'],
        };
    }

    /**
     * add warning to admin through Warning_System
     */
    private function addWarningToAdmin(AdminCard $card, int $actorId, string $actorName, string $actorServer): array
    {
        $connection = self::SERVER_CONNECTIONS[$card->creator_server] ?? null;

        if (! $connection) {
            return ['success' => false, 'error' => 'invalid_server'];
        }

        // get target admin
        $targetAdmin = $this->gameService->getAdminByName($card->creator_server, $card->target_admin_name);

        if (! $targetAdmin) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        // check if admin already has 3 warnings
        if (($targetAdmin->Preds ?? 0) >= 3) {
            return ['success' => false, 'error' => 'max_warnings_reached'];
        }

        $newWarnings = ($targetAdmin->Preds ?? 0) + 1;

        // update warnings in a27dmins table
        DB::connection($connection)
            ->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$card->target_admin_name])
            ->update(['Preds' => $newWarnings]);

        // log to logsadmin2 for arkxa bot notification
        DB::connection($connection)->table('logsadmin2')->insert([
            'idadm' => $targetAdmin->ID ?? $targetAdmin->idAccount,
            'name' => $card->target_admin_name,
            'ip' => $targetAdmin->IP ?? '',
            'admin' => $actorName,
            'ipp' => request()->ip() ?? '',
            'type' => 1, // type 1 = warning_add
            'kolvo' => $newWarnings,
            'reason' => $card->reason,
            'date' => now('Europe/Moscow'),
        ]);

        Log::info('Warning added to admin via card system', [
            'card_id' => $card->id,
            'admin_name' => $card->target_admin_name,
            'admin_id' => $targetAdmin->ID ?? $targetAdmin->idAccount,
            'new_warnings' => $newWarnings,
            'reason' => $card->reason,
        ]);

        return ['success' => true, 'new_warnings' => $newWarnings];
    }

    /**
     * remove warning from admin through Warning_System
     */
    private function removeWarningFromAdmin(AdminCard $card, int $actorId, string $actorName, string $actorServer): array
    {
        $connection = self::SERVER_CONNECTIONS[$card->creator_server] ?? null;

        if (! $connection) {
            return ['success' => false, 'error' => 'invalid_server'];
        }

        // get target admin
        $targetAdmin = $this->gameService->getAdminByName($card->creator_server, $card->target_admin_name);

        if (! $targetAdmin) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        // check if admin has any warnings
        if (($targetAdmin->Preds ?? 0) < 1) {
            return ['success' => false, 'error' => 'no_active_warning'];
        }

        $newWarnings = ($targetAdmin->Preds ?? 0) - 1;

        // update warnings in a27dmins table
        DB::connection($connection)
            ->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$card->target_admin_name])
            ->update(['Preds' => $newWarnings]);

        // log to logsadmin2 for arkxa bot notification
        DB::connection($connection)->table('logsadmin2')->insert([
            'idadm' => $targetAdmin->ID ?? $targetAdmin->idAccount,
            'name' => $card->target_admin_name,
            'ip' => $targetAdmin->IP ?? '',
            'admin' => $actorName,
            'ipp' => request()->ip() ?? '',
            'type' => 2, // type 2 = warning_remove
            'kolvo' => $newWarnings,
            'reason' => $card->reason,
            'date' => now('Europe/Moscow'),
        ]);

        Log::info('Warning removed from admin via card system', [
            'card_id' => $card->id,
            'admin_name' => $card->target_admin_name,
            'admin_id' => $targetAdmin->ID ?? $targetAdmin->idAccount,
            'new_warnings' => $newWarnings,
            'reason' => $card->reason,
        ]);

        return ['success' => true, 'new_warnings' => $newWarnings];
    }

    /**
     * level up admin
     */
    private function levelUpAdmin(AdminCard $card, int $actorId, string $actorName, string $actorServer): array
    {
        $connection = self::SERVER_CONNECTIONS[$card->creator_server] ?? null;

        if (! $connection) {
            return ['success' => false, 'error' => 'invalid_server'];
        }

        // get target admin
        $targetAdmin = $this->gameService->getAdminByName($card->creator_server, $card->target_admin_name);

        if (! $targetAdmin) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        $currentLevel = $targetAdmin->Level ?? 0;
        $newLevel = $currentLevel + 1;

        // update level in a27dmins table
        DB::connection($connection)
            ->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$card->target_admin_name])
            ->update(['Level' => $newLevel]);

        // log to logsadmin2
        DB::connection($connection)->table('logsadmin2')->insert([
            'idadm' => $targetAdmin->ID ?? $targetAdmin->idAccount,
            'name' => $card->target_admin_name,
            'ip' => $targetAdmin->IP ?? '',
            'admin' => $actorName,
            'ipp' => request()->ip() ?? '',
            'type' => 3, // type 3 = level_up
            'kolvo' => $newLevel,
            'reason' => $card->reason,
            'date' => now('Europe/Moscow'),
        ]);

        Log::info('Admin level increased via card system', [
            'card_id' => $card->id,
            'admin_name' => $card->target_admin_name,
            'old_level' => $currentLevel,
            'new_level' => $newLevel,
        ]);

        return ['success' => true, 'old_level' => $currentLevel, 'new_level' => $newLevel];
    }

    /**
     * level down admin
     */
    private function levelDownAdmin(AdminCard $card, int $actorId, string $actorName, string $actorServer): array
    {
        $connection = self::SERVER_CONNECTIONS[$card->creator_server] ?? null;

        if (! $connection) {
            return ['success' => false, 'error' => 'invalid_server'];
        }

        // get target admin
        $targetAdmin = $this->gameService->getAdminByName($card->creator_server, $card->target_admin_name);

        if (! $targetAdmin) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        $currentLevel = $targetAdmin->Level ?? 0;

        if ($currentLevel < 1) {
            return ['success' => false, 'error' => 'already_minimum_level'];
        }

        $newLevel = $currentLevel - 1;

        // update level in a27dmins table
        DB::connection($connection)
            ->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$card->target_admin_name])
            ->update(['Level' => $newLevel]);

        // log to logsadmin2
        DB::connection($connection)->table('logsadmin2')->insert([
            'idadm' => $targetAdmin->ID ?? $targetAdmin->idAccount,
            'name' => $card->target_admin_name,
            'ip' => $targetAdmin->IP ?? '',
            'admin' => $actorName,
            'ipp' => request()->ip() ?? '',
            'type' => 4, // type 4 = level_down
            'kolvo' => $newLevel,
            'reason' => $card->reason,
            'date' => now('Europe/Moscow'),
        ]);

        Log::info('Admin level decreased via card system', [
            'card_id' => $card->id,
            'admin_name' => $card->target_admin_name,
            'old_level' => $currentLevel,
            'new_level' => $newLevel,
        ]);

        return ['success' => true, 'old_level' => $currentLevel, 'new_level' => $newLevel];
    }
}
