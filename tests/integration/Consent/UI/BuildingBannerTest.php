<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\UI;

use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\UI\BuildingBanner;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BuildingBannerTest extends WPTestCase
{
    public function testTheTextsCanBeChanged(): void
    {
        $sut = new BuildingBanner([BuildingBanner::REJECT_ALL => 'Reject all'], []);

        $sut->changeText(BuildingBanner::REJECT_ALL, 'Only necessary');

        $this->assertSame([BuildingBanner::REJECT_ALL => 'Only necessary'], $sut->texts());
    }

    public function testAnUnknownTextIsRefused(): void
    {
        $sut = new BuildingBanner([BuildingBanner::REJECT_ALL => 'Reject all'], []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The banner has no "unknown" text');

        $sut->changeText('unknown', 'Text');
    }

    public function testTheCategoryLabelsCanBeChanged(): void
    {
        $sut = new BuildingBanner([], ['statistics' => ['label' => 'Statistics', 'description' => 'Visits']]);

        $sut->changeCategoryLabel('statistics', 'Analytics');
        $sut->changeCategoryLabel('social', 'Social', 'Share buttons');

        $this->assertSame(
            ['label' => 'Analytics', 'description' => ''],
            $sut->categoryLabel(new Category('statistics'))
        );
        $this->assertSame(
            ['label' => 'Social', 'description' => 'Share buttons'],
            $sut->categoryLabel(new Category('social'))
        );
    }

    public function testACategoryWithoutLabelUsesItsId(): void
    {
        $this->assertSame(
            ['label' => 'marketing', 'description' => ''],
            (new BuildingBanner([], []))->categoryLabel(new Category('marketing'))
        );
    }
}
