<?php

namespace Tests\Unit\Support;

use App\Support\PlayerSearchQuery;
use Tests\TestCase;

class PlayerSearchQueryTest extends TestCase
{
    public function test_tokens_treat_slash_and_spaces_as_separators(): void
    {
        $this->assertSame(['kaka', 'ronaldo'], PlayerSearchQuery::nameSearchTokens('kaka ronaldo'));
        $this->assertSame(['kaka', 'ronaldo'], PlayerSearchQuery::nameSearchTokens('kaka / ronaldo'));
        $this->assertSame(['kaka', 'ronaldo'], PlayerSearchQuery::nameSearchTokens('kaka  /  ronaldo'));
    }

    public function test_single_word_one_token(): void
    {
        $this->assertSame(['messi'], PlayerSearchQuery::nameSearchTokens('messi'));
    }

    public function test_commas_also_split(): void
    {
        $this->assertSame(['a', 'b'], PlayerSearchQuery::nameSearchTokens('a, b'));
    }
}
