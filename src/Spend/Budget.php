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
            ->increment('spend_usd_micros', $estimate) === 1;
    }

    public function refund(int $estimateUsdMicros): void
    {
        $estimate = max($estimateUsdMicros, 0);

        if ($estimate === 0 || $this->policy->cap() === null) {
            return;
        }

        $key = $this->ledger()->getKey();

        /*
         * Two statements rather than one CASE, so the amount is bound rather
         * than concatenated into the SET clause, and a transaction so a
         * record() landing between them is not clobbered by the clamp.
         */
        DB::connection(Aria::connection())->transaction(function () use ($key, $estimate): void {
            $this->ledgerQuery()
                ->whereKey($key)
                ->where('spend_usd_micros', '>', $estimate)
                ->decrement('spend_usd_micros', $estimate);

            $this->ledgerQuery()
                ->whereKey($key)
                ->where('spend_usd_micros', '<=', $estimate)
                ->update(['spend_usd_micros' => 0]);
        });
    }

    public function record(int $usdMicros): void
    {
        $spend = max($usdMicros, 0);

        if ($spend === 0) {
            return;
        }

        $this->ledgerQuery()
            ->whereKey($this->ledger()->getKey())
            ->increment('spend_usd_micros', $spend);
    }

    public function estimateFor(string $input): int
    {
        $tokens = (int) ceil(mb_strlen($input) / self::CHARS_PER_TOKEN);
        $table = config('aria.pricing');

        $dearest = 0;

        foreach (is_array($table) ? $table : [] as $prices) {
            $dearest = max($dearest, is_array($prices) ? self::asInt($prices['output'] ?? 0) : 0);
        }

        return intdiv($tokens * 2 * $dearest, 1_000_000);
    }

    public function spentThisPeriod(): int
    {
        return self::asInt($this->ledger()->getAttribute('spend_usd_micros'));
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

    /** An attribute read back off a model is mixed until something narrows it. */
    private static function asInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function ledger(): SpendLedger
    {
        return $this->ledgerQuery()->firstOrCreate(
            ['scope' => $this->policy->scope(), 'period' => now()->format('Y-m')],
            ['spend_usd_micros' => 0],
        );
    }
}
