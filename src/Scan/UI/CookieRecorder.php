<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Scan\UI;

use ItalyCookieChoices\Scan\Application\ServerCookies;
use ItalyStrap\Asset\AssetLoader;
use ItalyStrap\Asset\BuildMetadataFile;
use ItalyStrap\Asset\Handle;
use ItalyStrap\Asset\RegisterOnly;
use ItalyStrap\Asset\Script;
use ItalyStrap\Asset\ScriptLoader;
use ItalyStrap\Event\SubscriberInterface;
use SplFileInfo;

/**
 * In scan mode, records the cookies the page sets, for the scan of the cookies in the browser:
 *
 * * the recorder of assets/src/cookie-recorder, first in the head, sees the cookies the scripts write;
 * * the footer prints the cookies of the Set-Cookie headers, HttpOnly included.
 *
 * The settings page reads both from the frame of the page.
 */
final readonly class CookieRecorder implements SubscriberInterface
{
    public const HANDLE = 'italy-cookie-choices-cookie-recorder';
    /** The global variable of the cookies of the server, read by assets/src/admin/frame-scan.ts. */
    public const SERVER_COOKIES = 'italyCookieChoicesServerCookies';

    private const SCRIPT = 'cookie-recorder.js';

    /**
     * @var \Closure(): list<string>
     */
    private \Closure $headers;

    /**
     * @param string $buildPath The assets/build folder, with the trailing slash.
     * @param string $buildUrl The assets/build URL, with the trailing slash.
     * @param (\Closure(): list<string>)|null $headers The headers of the response, headers_list() by default.
     */
    public function __construct(
        private ScanRequest $request,
        private ServerCookies $serverCookies,
        private AssetLoader $assets,
        private ScriptLoader $scripts,
        private string $buildPath,
        private string $buildUrl,
        ?\Closure $headers = null,
    ) {
        $this->headers = $headers ?? \headers_list(...);
    }

    public function getSubscribedEvents(): iterable
    {
        // Before every other script of the head.
        yield 'wp_head' => [
            self::CALLBACK => 'printRecorder',
            self::PRIORITY => \PHP_INT_MIN,
        ];
        // After the scripts of the footer, when the headers are set.
        yield 'wp_footer' => [
            self::CALLBACK => 'printServerCookies',
            self::PRIORITY => \PHP_INT_MAX,
        ];
    }

    public function printRecorder(): void
    {
        $file = new BuildMetadataFile(new SplFileInfo($this->buildPath . self::SCRIPT));
        if (!$this->request->isScan() || !$file->exists()) {
            return;
        }

        $metadata = $file->read();
        $this->assets->load(new Script(
            handle: new Handle(self::HANDLE),
            source: $this->buildUrl . self::SCRIPT,
            dependencies: $metadata->dependencies(),
            version: $metadata->version(),
            condition: new RegisterOnly(),
        ));
        $this->scripts->print(new Handle(self::HANDLE));
    }

    public function printServerCookies(): void
    {
        if (!$this->request->isScan()) {
            return;
        }

        $cookies = $this->serverCookies->from(($this->headers)(), new \DateTimeImmutable());
        \wp_print_inline_script_tag(
            \sprintf('window.%s = %s;', self::SERVER_COOKIES, (string) \wp_json_encode($cookies))
        );
    }
}
