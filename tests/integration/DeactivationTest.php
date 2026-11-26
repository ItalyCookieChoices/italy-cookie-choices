<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use ItalyCookieChoices\ConsentLog\UI\RetentionCleanup;
use ItalyCookieChoices\Scan\UI\ScanCron;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class DeactivationTest extends WPTestCase
{
    public function testTheDeactivationClearsTheEventsOfWpCron(): void
    {
        \wp_schedule_event(\time(), 'daily', RetentionCleanup::EVENT_NAME);
        \wp_schedule_event(\time(), 'weekly', ScanCron::RUN);
        \wp_schedule_single_event(\time(), ScanCron::BATCH);

        \do_action('deactivate_italy-cookie-choices/italy-cookie-choices.php');

        $this->assertFalse(\wp_next_scheduled(RetentionCleanup::EVENT_NAME));
        $this->assertFalse(\wp_next_scheduled(ScanCron::RUN));
        $this->assertFalse(\wp_next_scheduled(ScanCron::BATCH));
    }
}
