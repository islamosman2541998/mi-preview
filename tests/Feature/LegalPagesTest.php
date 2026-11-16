<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    #[Test]
    #[DataProvider('legalPages')]
    public function legal_pages_are_public_on_the_admin_host(string $path, string $text): void
    {
        $this->get("http://app.localhost/{$path}")
            ->assertOk()
            ->assertSee($text)
            ->assertSee('privacy@mius.it.com')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public static function legalPages(): array
    {
        return [
            ['privacy-policy', 'Privacy Policy'],
            ['terms', 'Terms of Use'],
            ['data-deletion', 'Data Deletion Instructions'],
        ];
    }
}
