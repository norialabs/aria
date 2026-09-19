<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use NoriaLabs\Aria\Aria;

abstract class AriaModel extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected string $ariaTable = '';

    public function getTable(): string
    {
        return Aria::table($this->ariaTable);
    }

    public function getConnectionName(): ?string
    {
        return Aria::connection() ?? parent::getConnectionName();
    }
}
