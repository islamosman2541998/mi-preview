<?php

namespace Tests\Feature;

use App\Rules\SiteSlug;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteSlugRuleTest extends TestCase
{
    private function passes(string $slug): bool
    {
        $validator = Validator::make(['slug' => $slug], ['slug' => ['required', new SiteSlug]]);

        // the static helper (used by the host resolver) must agree with the rule
        $this->assertSame($validator->passes(), SiteSlug::passes($slug), "Rule/helper disagree on [{$slug}]");

        return $validator->passes();
    }

    #[Test]
    #[DataProvider('validSlugs')]
    public function accepts_valid_slugs(string $slug): void
    {
        $this->assertTrue($this->passes($slug), "Expected [{$slug}] to be valid");
    }

    public static function validSlugs(): array
    {
        return [['acme'], ['abc'], ['al-noor-clinic'], ['co2'], ['a1-b2-c3'], [str_repeat('a', 40)]];
    }

    #[Test]
    #[DataProvider('invalidSlugs')]
    public function rejects_badly_formatted_slugs(string $slug): void
    {
        $this->assertFalse($this->passes($slug), "Expected [{$slug}] to be invalid");
    }

    public static function invalidSlugs(): array
    {
        return [
            'too short' => ['ab'],
            'too long' => [str_repeat('a', 41)],
            'uppercase' => ['Acme'],
            'leading hyphen' => ['-acme'],
            'trailing hyphen' => ['acme-'],
            'underscore' => ['acme_co'],
            'dot' => ['acme.co'],
            'space' => ['acme co'],
            'arabic' => ['شركة'],
            'empty' => [''],
        ];
    }

    #[Test]
    #[DataProvider('reservedSlugs')]
    public function rejects_reserved_slugs(string $slug): void
    {
        $this->assertFalse($this->passes($slug), "Expected [{$slug}] to be reserved");
    }

    public static function reservedSlugs(): array
    {
        return [
            ['www'], ['app'], ['admin'], ['api'], ['mail'], ['cpanel'], ['autodiscover'], ['preview'], ['demo'], ['test'],
            ['clinic'], ['funnycouples'], ['mi-solutions'], ['rk-fire'], ['riwaq'], ['abouda-elashmawy'],
        ];
    }

    #[Test]
    public function admin_subdomain_is_always_reserved_even_if_renamed(): void
    {
        config(['preview.admin_subdomain' => 'panel']);

        $this->assertFalse($this->passes('panel'));
    }

    #[Test]
    public function reserved_list_is_extendable_from_config(): void
    {
        $this->assertTrue($this->passes('newproject'));

        config(['preview.reserved_slugs' => [...config('preview.reserved_slugs'), 'newproject']]);

        $this->assertFalse($this->passes('newproject'));
    }
}
