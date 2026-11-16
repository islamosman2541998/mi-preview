<?php

namespace Tests\Feature;

use App\Enums\SectionType;
use App\Filament\Support\SectionFields;
use App\Models\Site;
use App\Services\SectionDefaults;
use App\Support\ServiceIcons;
use Database\Seeders\DemoSiteSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionFieldsTest extends TestCase
{
    #[Test]
    public function form_mapping_round_trips_for_every_type(): void
    {
        $site = Site::factory()->make(['company_name_ar' => 'أكمي', 'company_name_en' => 'Acme']);
        $demo = DemoSiteSeeder::content();

        foreach (SectionType::cases() as $type) {
            $content = $demo[$type->value] ?? SectionDefaults::for($type, $site);

            $once = SectionFields::fromForm($type, SectionFields::toForm($type, $content));
            $twice = SectionFields::fromForm($type, SectionFields::toForm($type, $once));

            $this->assertSame($once, $twice, "{$type->value} does not round-trip");
            $this->assertNotEmpty(SectionFields::components($type, 1), "{$type->value} has no form");
        }
    }

    #[Test]
    public function items_keep_languages_and_shared_values_aligned(): void
    {
        $form = ['ar' => ['title' => 'مشاريع'], 'en' => ['title' => 'Projects'], 'items' => [
            ['title_ar' => 'أ', 'title_en' => 'A', 'location_ar' => null, 'location_en' => null, 'category_ar' => null, 'category_en' => null, 'description_ar' => null, 'description_en' => null, 'image' => 'a.jpg', 'year' => '2024'],
            ['title_ar' => 'ب', 'title_en' => null, 'image' => null, 'year' => ''],
        ]];

        $content = SectionFields::fromForm(SectionType::Projects, $form);

        $this->assertSame(['أ', 'ب'], array_column($content['ar']['items'], 'title'));
        $this->assertSame(['A', null], array_column($content['en']['items'], 'title'));
        $this->assertSame([['image' => 'a.jpg', 'year' => 2024], ['image' => null, 'year' => null]], $content['shared']['items']);
    }

    #[Test]
    public function menu_prices_follow_categories_and_dishes(): void
    {
        $form = ['ar' => ['title' => 'المنيو'], 'en' => ['title' => 'Menu'], 'menu' => [
            ['name_ar' => 'مشويات', 'name_en' => 'Grills', 'dishes' => [
                ['name_ar' => 'كفتة', 'name_en' => 'Kofta', 'description_ar' => null, 'description_en' => null, 'price' => '180'],
                ['name_ar' => 'ريش', 'name_en' => 'Chops', 'price' => null],
            ]],
        ]];

        $content = SectionFields::fromForm(SectionType::Menu, $form);

        $this->assertSame('Kofta', $content['en']['categories'][0]['items'][0]['name']);
        $this->assertSame([[180, null]], $content['shared']['prices']);
        $this->assertSame($form['menu'][0]['dishes'][0]['price'], (string) SectionFields::toForm(SectionType::Menu, $content)['menu'][0]['dishes'][0]['price']);
    }

    #[Test]
    public function blank_strings_are_stored_as_null(): void
    {
        $content = SectionFields::fromForm(SectionType::Contact, ['ar' => ['title' => '  ', 'body' => 'نص', 'hours' => ''], 'en' => []]);

        $this->assertNull($content['ar']['title']);
        $this->assertSame('نص', $content['ar']['body']);
        $this->assertArrayNotHasKey('shared', $content);
    }

    #[Test]
    public function new_image_slots_and_service_icon_round_trip(): void
    {
        $about = SectionFields::fromForm(SectionType::About, ['ar' => ['title' => 'ع'], 'shared' => ['image' => 'a.jpg', 'image2' => 'b.jpg', 'since' => '2009']]);
        $this->assertSame('b.jpg', $about['shared']['image2']);
        $this->assertSame('b.jpg', SectionFields::toForm(SectionType::About, $about)['shared']['image2']);

        foreach ([SectionType::Stats, SectionType::Cta, SectionType::Faq, SectionType::Booking] as $type) {
            $content = SectionFields::fromForm($type, ['ar' => ['title' => 'ع'], 'shared' => ['image' => 'bg.jpg']]);
            $this->assertSame('bg.jpg', $content['shared']['image'], $type->value);
            $this->assertSame('bg.jpg', SectionFields::toForm($type, $content)['shared']['image'], $type->value);
        }

        $services = SectionFields::fromForm(SectionType::Services, ['ar' => ['title' => 'خ'], 'items' => [
            ['title_ar' => 'حفر', 'title_en' => 'Excavation', 'image' => 's.jpg', 'icon' => 'crane'],
        ]]);
        $this->assertSame([['image' => 's.jpg', 'icon' => 'crane']], $services['shared']['items']);
        $this->assertSame('crane', SectionFields::toForm(SectionType::Services, $services)['items'][0]['icon']);
    }

    #[Test]
    public function service_icons_resolve_unknown_keys_to_default(): void
    {
        $this->assertSame('sparkle', ServiceIcons::resolve('nope'));
        $this->assertSame('sparkle', ServiceIcons::resolve(null));
        $this->assertSame('crane', ServiceIcons::resolve('crane'));
        $this->assertCount(30, ServiceIcons::KEYS);
        $this->assertSame(ServiceIcons::KEYS, array_keys(ServiceIcons::options()));
    }
}
