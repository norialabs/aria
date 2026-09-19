<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use NoriaLabs\Aria\Aria;
use Throwable;

final class Vectors
{
    private static ?bool $available = null;

    public static function available(): bool
    {
        if (self::$available !== null) {
            return self::$available;
        }

        if (DB::connection(Aria::connection())->getDriverName() !== 'pgsql') {
            return self::$available = false;
        }

        try {
            DB::connection(Aria::connection())->statement('CREATE EXTENSION IF NOT EXISTS vector');
        } catch (Throwable) {
        }

        try {
            return self::$available = DB::connection(Aria::connection())->scalar(
                "select count(*) from pg_extension where extname = 'vector'",
            ) > 0;
        } catch (Throwable) {
            return self::$available = false;
        }
    }

    public static function indexed(): bool
    {
        return self::available()
            && Schema::connection(Aria::connection())->hasColumn(Aria::table('chunks'), 'embedding');
    }

    public static function flush(): void
    {
        self::$available = null;
    }
}
