<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Spend;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use NoriaLabs\Aria\Aria;
use NoriaLabs\Aria\Contracts\BudgetPolicy;
use NoriaLabs\Aria\Models\SpendLedger;

class Budget
{
    private const CHARS_PER_TOKEN = 4;

    public function __construct(private BudgetPolicy $policy) {}

    public function allows(): bool
    {
        return $this->hasHeadroom(0);
    }

    public function reserve(int $estimateUsdMicros): bool
    {
        $estimate = max($estimateUsdMicros, 0);

        $ledger = $this->ledger();
        $cap = $this->policy->cap();

        if ($cap === null) {
            return true;
        }

        return $this->ledgerQuery()
            ->whereKey($ledger->getKey())
            ->whereRaw('spend_usd_micros + ? <= ?', [$estimate, $cap])
            ->update(['spend_usd_micros' => DB::raw('spend_usd_micros + '.$estimate)]) === 1;
    }

    public function refund(int $estimateUsdMicros): void
    {
        $estimate = max($estimateUsdMicros, 0);

        if ($estimate === 0 || $this->policy->cap() === null) {
            return;
        }

        $this->ledgerQuery()
            ->whereKey($this->ledger()->getKey())
            ->update(['spend_usd_micros' => DB::raw(
                'CASE WHEN spend_usd_micros > '.$estimate.' THEN spend_usd_micros - '.$estimate.' ELSE 0 END'
            )]);
    }

    public function record(int $usdMicros): void
    {
        $spend = max($usdMicros, 0);

        if ($spend === 0) {
            return;
        }

        $this->ledgerQuery()
            ->whereKey($this->ledger()->getKey())
            ->update(['spend_usd_micros' => DB::raw('spend_usd_micros + '.$spend)]);
    }

    public function estimateFor(string $input): int
    {
        $tokens = (int) ceil(mb_strlen($input) / self::CHARS_PER_TOKEN);
        $table = config('aria.pricing');

        $dearest = 0;

        foreach (is_array($table) ? $table : [] as $prices) {
            $dearest = max($dearest, (int) (is_array($prices) ? ($prices['output'] ?? 0) : 0));
        }

        return intdiv($tokens * 2 * $dearest, 1_000_000);
    }

    public function spentThisPeriod(): int
    {
        return (int) $this->ledger()->getAttribute('spend_usd_micros');
    }

    private function hasHeadroom(int $estimateUsdMicros): bool
    {
        $cap = $this->policy->cap();

        if ($cap === null) {
            return true;
        }

        return $this->spentThisPeriod() + max($estimateUsdMicros, 0) <= $cap;
    }

    /** @return Builder<SpendLedger> */
    private function ledgerQuery(): Builder
    {
        /** @var Builder<SpendLedger> */
        return Aria::spendLedgerModel()::query();
    }

    private function ledger(): SpendLedger
    {
        return $this->ledgerQuery()->firstOrCreate(
            ['scope' => $this->policy->scope(), 'period' => now()->format('Y-m')],
            ['spend_usd_micros' => 0],
        );
    }
}
