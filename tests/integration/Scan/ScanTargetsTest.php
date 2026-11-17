<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration\Scan;

use ItalyCookieChoices\Scan\Application\ScanTargets;
use ItalyCookieChoices\Scan\Domain\Selection;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class ScanTargetsTest extends WPTestCase
{
    public function testTheHomePageIsTheTargetZero(): void
    {
        $this->assertSame(
            [['id' => 0, 'title' => 'Home page', 'url' => \home_url('/')]],
            (new ScanTargets())->of(new Selection(home: true))->targets()
        );
    }

    public function testItFindsThePublishedContentOfThePostTypesTheCategoriesAndThePosts(): void
    {
        $page = $this->post('page', 'About');
        $draft = $this->post('page', 'Draft', 'draft');
        $news = self::factory()->category->create(['name' => 'News']);
        $this->assertIsInt($news);
        $inNews = $this->post('post', 'In the news');
        \wp_set_post_categories($inNews, [$news]);
        $other = $this->post('post', 'Other');

        $ids = \array_column((new ScanTargets())->of(new Selection(
            postTypes: ['page'],
            categories: [$news],
            posts: [$other, $draft, $page],
        ))->targets(), 'id');

        $this->assertEqualsCanonicalizing([$page, $inNews, $other], $ids);
    }

    public function testThePostTypesMustBeViewable(): void
    {
        $this->post('wp_block', 'Pattern');

        $this->assertSame([], (new ScanTargets())->of(new Selection(postTypes: ['wp_block', 'missing']))->targets());
    }

    public function testTheTargetsAreLimited(): void
    {
        foreach (\range(1, 4) as $number) {
            $this->post('post', 'Post ' . $number);
        }

        $result = (new ScanTargets(3))->of(new Selection(home: true, postTypes: ['post']));

        $this->assertCount(3, $result->targets());
        $this->assertSame(0, $result->targets()[0]['id'] ?? null);
        $this->assertTrue($result->isTruncated());
    }

    public function testTheUrlOfATarget(): void
    {
        $page = $this->post('page', 'About');
        $draft = $this->post('page', 'Draft', 'draft');
        $sut = new ScanTargets();

        $this->assertSame(\home_url('/'), $sut->urlOf(0));
        $this->assertSame(\get_permalink($page), $sut->urlOf($page));
        $this->assertSame('', $sut->urlOf($draft));
        $this->assertSame('', $sut->urlOf(99999));
    }

    private function post(string $type, string $title, string $status = 'publish'): int
    {
        $postId = self::factory()->post->create([
            'post_type' => $type,
            'post_title' => $title,
            'post_status' => $status,
        ]);
        $this->assertIsInt($postId);

        return $postId;
    }
}
