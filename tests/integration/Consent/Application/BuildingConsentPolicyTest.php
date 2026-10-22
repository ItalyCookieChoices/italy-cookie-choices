<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Consent\Application;

use ItalyCookieChoices\Consent\Application\BuildingConsentPolicy;
use ItalyCookieChoices\Consent\Domain\Categories;
use ItalyCookieChoices\Consent\Domain\Category;
use ItalyCookieChoices\Consent\Domain\ConsentDuration;
use ItalyCookieChoices\Consent\Domain\PolicyVersion;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class BuildingConsentPolicyTest extends WPTestCase
{
    public function testTheNecessaryCategoryIsAlwaysFirstAndRequired(): void
    {
        $sut = $this->makeSut(new Category('statistics'));

        $sut->addCategory(new Category(Category::NECESSARY));

        $this->assertSame(['necessary', 'statistics'], $sut->categories()->ids());
        $this->assertSame(['necessary'], $sut->categories()->requiredIds());
    }

    public function testTheNecessaryCategoryCannotBeRemoved(): void
    {
        $sut = $this->makeSut(new Category(Category::NECESSARY, true));

        $sut->removeCategory(Category::NECESSARY);

        $this->assertSame(['necessary'], $sut->categories()->ids());
    }

    public function testACategoryWithTheSameIdIsReplacedInItsPlace(): void
    {
        $sut = $this->makeSut(new Category('statistics'), new Category('marketing'));

        $sut->addCategory(new Category('statistics', true));

        $this->assertSame(['necessary', 'statistics', 'marketing'], $sut->categories()->ids());
        $this->assertSame(['necessary', 'statistics'], $sut->categories()->requiredIds());
    }

    public function testRemovingAMissingCategoryDoesNothing(): void
    {
        $sut = $this->makeSut(new Category('statistics'));

        $sut->removeCategory('missing');

        $this->assertSame(['necessary', 'statistics'], $sut->categories()->ids());
    }

    public function testThePolicyHasTheChanges(): void
    {
        $sut = $this->makeSut(new Category('statistics'));

        $sut->changeVersion(new PolicyVersion('3'));
        $sut->changeDuration(new ConsentDuration(10));
        $policy = $sut->policy();

        $this->assertSame(['necessary', 'statistics'], $policy->categories()->ids());
        $this->assertSame('3', $sut->version()->toString());
        $this->assertSame('3', $policy->version()->toString());
        $this->assertSame(10, $sut->duration()->days());
        $this->assertSame(10, $policy->duration()->days());
    }

    private function makeSut(Category ...$categories): BuildingConsentPolicy
    {
        return new BuildingConsentPolicy(new Categories(...$categories), new PolicyVersion('1'), new ConsentDuration());
    }
}
