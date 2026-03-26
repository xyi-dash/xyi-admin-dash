<?php

namespace Tests\Unit;

use App\Models\AdminBan;
use App\Models\AdminCard;
use App\Services\ActionLogService;
use App\Services\AdminCardService;
use App\Services\GameAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminCardServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminCardService $service;
    private ActionLogService $actionLog;
    private GameAccountService $gameService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actionLog = $this->createMock(ActionLogService::class);
        $this->gameService = $this->createMock(GameAccountService::class);
        $this->service = new AdminCardService($this->actionLog, $this->gameService);
    }

    public function test_create_card_creates_pending_card(): void
    {
        $data = [
            'target_admin_name' => 'TestAdmin',
            'action_type' => 'warning_add',
            'reason' => 'Test reason for warning',
            'evidence' => 'Test evidence',
        ];

        $card = $this->service->createCard($data, 123, 'CreatorAdmin', 'one');

        $this->assertInstanceOf(AdminCard::class, $card);
        $this->assertEquals('pending', $card->status);
        $this->assertEquals('TestAdmin', $card->target_admin_name);
        $this->assertEquals('warning_add', $card->action_type);
        $this->assertEquals('CreatorAdmin', $card->creator_name);
    }

    public function test_get_pending_cards_returns_only_pending(): void
    {
        AdminCard::factory()->create(['status' => 'pending', 'creator_server' => 'one']);
        AdminCard::factory()->create(['status' => 'approved', 'creator_server' => 'one']);
        AdminCard::factory()->create(['status' => 'pending', 'creator_server' => 'two']);

        $cards = $this->service->getPendingCards();

        $this->assertCount(2, $cards);
        $this->assertTrue($cards->every(fn ($card) => $card->status === 'pending'));
    }

    public function test_get_pending_cards_filters_by_server(): void
    {
        AdminCard::factory()->create(['status' => 'pending', 'creator_server' => 'one']);
        AdminCard::factory()->create(['status' => 'pending', 'creator_server' => 'two']);

        $cards = $this->service->getPendingCards('one');

        $this->assertCount(1, $cards);
        $this->assertEquals('one', $cards->first()->creator_server);
    }

    public function test_review_card_rejects_card(): void
    {
        $card = AdminCard::factory()->create(['status' => 'pending']);

        $result = $this->service->reviewCard($card->id, 'reject', 456, 'ReviewerAdmin', 'one');

        $this->assertTrue($result['success']);
        $this->assertEquals('rejected', $result['status']);

        $card->refresh();
        $this->assertEquals('rejected', $card->status);
        $this->assertEquals(456, $card->reviewed_by);
        $this->assertNotNull($card->reviewed_at);
    }

    public function test_review_card_returns_error_for_nonexistent_card(): void
    {
        $result = $this->service->reviewCard(99999, 'approve', 456, 'ReviewerAdmin', 'one');

        $this->assertFalse($result['success']);
        $this->assertEquals('card_not_found', $result['error']);
    }

    public function test_review_card_returns_error_for_already_processed_card(): void
    {
        $card = AdminCard::factory()->create(['status' => 'approved']);

        $result = $this->service->reviewCard($card->id, 'approve', 456, 'ReviewerAdmin', 'one');

        $this->assertFalse($result['success']);
        $this->assertEquals('card_already_processed', $result['error']);
    }

    public function test_approve_permanent_ban_requires_confirmation(): void
    {
        $card = AdminCard::factory()->create([
            'status' => 'pending',
            'action_type' => 'permanent_ban',
        ]);

        $result = $this->service->reviewCard($card->id, 'approve', 456, 'ReviewerAdmin', 'one');

        $this->assertTrue($result['success']);
        $this->assertEquals('requires_confirmation', $result['status']);
        $this->assertTrue($result['requires_confirmation']);

        $card->refresh();
        $this->assertEquals('requires_confirmation', $card->status);
    }

    public function test_confirm_permanent_ban_returns_error_for_invalid_status(): void
    {
        $card = AdminCard::factory()->create(['status' => 'pending']);

        $result = $this->service->confirmPermanentBan($card->id, 456, 'ConfirmerAdmin', 'one');

        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_status', $result['error']);
    }

    public function test_confirm_permanent_ban_returns_error_for_invalid_action_type(): void
    {
        $card = AdminCard::factory()->create([
            'status' => 'requires_confirmation',
            'action_type' => 'warning_add',
        ]);

        $result = $this->service->confirmPermanentBan($card->id, 456, 'ConfirmerAdmin', 'one');

        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_action_type', $result['error']);
    }

    public function test_add_warning_to_admin_increases_warning_count(): void
    {
        $targetAdmin = (object) [
            'ID' => 789,
            'Name' => 'TargetAdmin',
            'Preds' => 1,
            'IP' => '127.0.0.1',
        ];

        $this->gameService
            ->expects($this->once())
            ->method('getAdminByName')
            ->with('one', 'TargetAdmin')
            ->willReturn($targetAdmin);

        DB::shouldReceive('connection')
            ->with('gangwar')
            ->andReturnSelf();

        DB::shouldReceive('table')
            ->with('a27dmins')
            ->andReturnSelf();

        DB::shouldReceive('whereRaw')
            ->with('BINARY Name = ?', ['TargetAdmin'])
            ->andReturnSelf();

        DB::shouldReceive('update')
            ->with(['Preds' => 2])
            ->andReturn(1);

        DB::shouldReceive('table')
            ->with('logsadmin2')
            ->andReturnSelf();

        DB::shouldReceive('insert')
            ->andReturn(true);

        $card = AdminCard::factory()->create([
            'target_admin_name' => 'TargetAdmin',
            'action_type' => 'warning_add',
            'reason' => 'Test warning',
            'creator_server' => 'one',
        ]);

        $result = $this->service->executeCardAction($card, 456, 'ActorAdmin', 'one');

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['new_warnings']);
    }

    public function test_remove_warning_returns_error_when_no_warnings(): void
    {
        $targetAdmin = (object) [
            'ID' => 789,
            'Name' => 'TargetAdmin',
            'Preds' => 0,
            'IP' => '127.0.0.1',
        ];

        $this->gameService
            ->expects($this->once())
            ->method('getAdminByName')
            ->with('one', 'TargetAdmin')
            ->willReturn($targetAdmin);

        $card = AdminCard::factory()->create([
            'target_admin_name' => 'TargetAdmin',
            'action_type' => 'warning_remove',
            'reason' => 'Test warning removal',
            'creator_server' => 'one',
        ]);

        $result = $this->service->executeCardAction($card, 456, 'ActorAdmin', 'one');

        $this->assertFalse($result['success']);
        $this->assertEquals('no_active_warning', $result['error']);
    }
}
