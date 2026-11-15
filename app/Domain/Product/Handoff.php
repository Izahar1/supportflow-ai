<?php

namespace App\Domain\Product;

final class Handoff
{
    public function canAutomate(string $status, string $mode): bool
    {
        return $status === 'open' && $mode === 'approved_auto';
    }

    public function request(): array
    {
        return ['status' => 'handoff_requested', 'ai_mode' => 'disabled'];
    }
}
