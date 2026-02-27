<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdObjective;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\PaidAdvertising\ValueObjects\BudgetType;
use App\Domain\PaidAdvertising\ValueObjects\MetricPeriod;

// ──────────────────────────────────────────────────────────────────
// AdProvider
// ──────────────────────────────────────────────────────────────────

it('ad provider returns correct labels', function () {
    expect(AdProvider::Meta->label())->toBe('Meta Ads')
        ->and(AdProvider::TikTok->label())->toBe('TikTok Ads')
        ->and(AdProvider::Google->label())->toBe('Google Ads');
});

it('all ad providers support boosting', function () {
    foreach (AdProvider::cases() as $provider) {
        expect($provider->supportsBoosting())->toBeTrue();
    }
});

it('only google requires developer token', function () {
    expect(AdProvider::Google->requiresDeveloperToken())->toBeTrue()
        ->and(AdProvider::Meta->requiresDeveloperToken())->toBeFalse()
        ->and(AdProvider::TikTok->requiresDeveloperToken())->toBeFalse();
});

// ──────────────────────────────────────────────────────────────────
// AdObjective
// ──────────────────────────────────────────────────────────────────

it('ad objective returns correct labels', function () {
    expect(AdObjective::Reach->label())->toBe('Alcance')
        ->and(AdObjective::Engagement->label())->toBe('Engajamento')
        ->and(AdObjective::Traffic->label())->toBe('Trafego')
        ->and(AdObjective::Conversions->label())->toBe('Conversoes');
});

it('ad objective returns platform mapping with all providers', function () {
    foreach (AdObjective::cases() as $objective) {
        $mapping = $objective->platformMapping();

        expect($mapping)->toHaveKeys(['meta', 'tiktok', 'google'])
            ->and($mapping['meta'])->toBeString()
            ->and($mapping['tiktok'])->toBeString()
            ->and($mapping['google'])->toBeString();
    }
});

// ──────────────────────────────────────────────────────────────────
// BudgetType
// ──────────────────────────────────────────────────────────────────

it('budget type returns correct labels', function () {
    expect(BudgetType::Daily->label())->toBe('Diario')
        ->and(BudgetType::Lifetime->label())->toBe('Vitalicio');
});

it('budget type has exactly two cases', function () {
    expect(BudgetType::cases())->toHaveCount(2);
});

// ──────────────────────────────────────────────────────────────────
// MetricPeriod
// ──────────────────────────────────────────────────────────────────

it('metric period returns correct labels', function () {
    expect(MetricPeriod::Hourly->label())->toBe('Por Hora')
        ->and(MetricPeriod::Daily->label())->toBe('Diario')
        ->and(MetricPeriod::Weekly->label())->toBe('Semanal')
        ->and(MetricPeriod::Lifetime->label())->toBe('Acumulado');
});

it('metric period has exactly four cases', function () {
    expect(MetricPeriod::cases())->toHaveCount(4);
});

// ──────────────────────────────────────────────────────────────────
// AdAccountStatus
// ──────────────────────────────────────────────────────────────────

it('ad account status returns correct labels', function () {
    expect(AdAccountStatus::Active->label())->toBe('Ativa')
        ->and(AdAccountStatus::TokenExpired->label())->toBe('Token Expirado')
        ->and(AdAccountStatus::Suspended->label())->toBe('Suspensa')
        ->and(AdAccountStatus::Disconnected->label())->toBe('Desconectada');
});

it('active account can transition to token_expired, suspended, disconnected', function () {
    expect(AdAccountStatus::Active->canTransitionTo(AdAccountStatus::TokenExpired))->toBeTrue()
        ->and(AdAccountStatus::Active->canTransitionTo(AdAccountStatus::Suspended))->toBeTrue()
        ->and(AdAccountStatus::Active->canTransitionTo(AdAccountStatus::Disconnected))->toBeTrue();
});

it('token_expired account can transition to active and disconnected', function () {
    expect(AdAccountStatus::TokenExpired->canTransitionTo(AdAccountStatus::Active))->toBeTrue()
        ->and(AdAccountStatus::TokenExpired->canTransitionTo(AdAccountStatus::Disconnected))->toBeTrue()
        ->and(AdAccountStatus::TokenExpired->canTransitionTo(AdAccountStatus::Suspended))->toBeFalse();
});

it('suspended account can transition to active and disconnected', function () {
    expect(AdAccountStatus::Suspended->canTransitionTo(AdAccountStatus::Active))->toBeTrue()
        ->and(AdAccountStatus::Suspended->canTransitionTo(AdAccountStatus::Disconnected))->toBeTrue()
        ->and(AdAccountStatus::Suspended->canTransitionTo(AdAccountStatus::TokenExpired))->toBeFalse();
});

it('disconnected account is terminal and cannot transition', function () {
    foreach (AdAccountStatus::cases() as $target) {
        expect(AdAccountStatus::Disconnected->canTransitionTo($target))->toBeFalse();
    }
});

it('only active account is operational', function () {
    expect(AdAccountStatus::Active->isOperational())->toBeTrue()
        ->and(AdAccountStatus::TokenExpired->isOperational())->toBeFalse()
        ->and(AdAccountStatus::Suspended->isOperational())->toBeFalse()
        ->and(AdAccountStatus::Disconnected->isOperational())->toBeFalse();
});

// ──────────────────────────────────────────────────────────────────
// AdStatus
// ──────────────────────────────────────────────────────────────────

it('ad status returns correct labels', function () {
    expect(AdStatus::Draft->label())->toBe('Rascunho')
        ->and(AdStatus::PendingReview->label())->toBe('Em Revisao')
        ->and(AdStatus::Active->label())->toBe('Ativo')
        ->and(AdStatus::Paused->label())->toBe('Pausado')
        ->and(AdStatus::Completed->label())->toBe('Concluido')
        ->and(AdStatus::Rejected->label())->toBe('Rejeitado')
        ->and(AdStatus::Cancelled->label())->toBe('Cancelado');
});

it('draft can transition to pending_review and cancelled only', function () {
    expect(AdStatus::Draft->canTransitionTo(AdStatus::PendingReview))->toBeTrue()
        ->and(AdStatus::Draft->canTransitionTo(AdStatus::Cancelled))->toBeTrue()
        ->and(AdStatus::Draft->canTransitionTo(AdStatus::Active))->toBeFalse()
        ->and(AdStatus::Draft->canTransitionTo(AdStatus::Paused))->toBeFalse()
        ->and(AdStatus::Draft->canTransitionTo(AdStatus::Completed))->toBeFalse();
});

it('pending_review can transition to active and rejected only', function () {
    expect(AdStatus::PendingReview->canTransitionTo(AdStatus::Active))->toBeTrue()
        ->and(AdStatus::PendingReview->canTransitionTo(AdStatus::Rejected))->toBeTrue()
        ->and(AdStatus::PendingReview->canTransitionTo(AdStatus::Cancelled))->toBeFalse()
        ->and(AdStatus::PendingReview->canTransitionTo(AdStatus::Paused))->toBeFalse();
});

it('active can transition to paused, completed, cancelled', function () {
    expect(AdStatus::Active->canTransitionTo(AdStatus::Paused))->toBeTrue()
        ->and(AdStatus::Active->canTransitionTo(AdStatus::Completed))->toBeTrue()
        ->and(AdStatus::Active->canTransitionTo(AdStatus::Cancelled))->toBeTrue()
        ->and(AdStatus::Active->canTransitionTo(AdStatus::Draft))->toBeFalse()
        ->and(AdStatus::Active->canTransitionTo(AdStatus::Rejected))->toBeFalse();
});

it('paused can transition to active, completed, cancelled', function () {
    expect(AdStatus::Paused->canTransitionTo(AdStatus::Active))->toBeTrue()
        ->and(AdStatus::Paused->canTransitionTo(AdStatus::Completed))->toBeTrue()
        ->and(AdStatus::Paused->canTransitionTo(AdStatus::Cancelled))->toBeTrue()
        ->and(AdStatus::Paused->canTransitionTo(AdStatus::Draft))->toBeFalse();
});

it('terminal statuses cannot transition to anything', function (AdStatus $terminal) {
    foreach (AdStatus::cases() as $target) {
        expect($terminal->canTransitionTo($target))->toBeFalse();
    }
})->with([
    'completed' => AdStatus::Completed,
    'rejected' => AdStatus::Rejected,
    'cancelled' => AdStatus::Cancelled,
]);

it('isTerminal returns true for completed, rejected, cancelled', function () {
    expect(AdStatus::Completed->isTerminal())->toBeTrue()
        ->and(AdStatus::Rejected->isTerminal())->toBeTrue()
        ->and(AdStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(AdStatus::Draft->isTerminal())->toBeFalse()
        ->and(AdStatus::PendingReview->isTerminal())->toBeFalse()
        ->and(AdStatus::Active->isTerminal())->toBeFalse()
        ->and(AdStatus::Paused->isTerminal())->toBeFalse();
});

it('isActive returns true only for active', function () {
    expect(AdStatus::Active->isActive())->toBeTrue()
        ->and(AdStatus::Draft->isActive())->toBeFalse()
        ->and(AdStatus::Paused->isActive())->toBeFalse()
        ->and(AdStatus::Completed->isActive())->toBeFalse();
});

it('canBeCancelled returns true for non-terminal non-review states', function () {
    expect(AdStatus::Draft->canBeCancelled())->toBeTrue()
        ->and(AdStatus::PendingReview->canBeCancelled())->toBeTrue()
        ->and(AdStatus::Active->canBeCancelled())->toBeTrue()
        ->and(AdStatus::Paused->canBeCancelled())->toBeTrue()
        ->and(AdStatus::Completed->canBeCancelled())->toBeFalse()
        ->and(AdStatus::Rejected->canBeCancelled())->toBeFalse()
        ->and(AdStatus::Cancelled->canBeCancelled())->toBeFalse();
});
