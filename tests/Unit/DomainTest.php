<?php

namespace Tests\Unit;

use App\Domain\Product\Handoff;
use PHPUnit\Framework\TestCase;

class DomainTest extends TestCase
{
    public function test_handoff_disables_automation(): void
    {
        $h = new Handoff;
        $result = $h->request();
        $this->assertFalse($h->canAutomate($result['status'], $result['ai_mode']));
    }

    public function test_drafts_are_never_autosent(): void
    {
        $this->assertFalse((new Handoff)->canAutomate('open', 'draft_only'));
    }

    public function test_closed_conversation_blocks_automation(): void
    {
        $this->assertFalse((new Handoff)->canAutomate('closed', 'approved_auto'));
    }

    public function test_only_explicit_approved_auto_mode_allows_automation(): void
    {
        $h = new Handoff;
        $this->assertFalse($h->canAutomate('open', 'disabled'));
        $this->assertTrue($h->canAutomate('open', 'approved_auto'));
    }
}
