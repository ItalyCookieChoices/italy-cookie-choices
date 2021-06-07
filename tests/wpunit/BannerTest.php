<?php
declare(strict_types=1);

namespace ItalyCookieChoices\Tests;

use Codeception\TestCase\WPTestCase;
use Italy_Cookie_Choices\Core\Cookie_Choices;
use ItalyStrap\Config\Config;
use Overclokk\Cookie\Cookie;

class BannerTest extends WPTestCase
{
    /**
     * @var \WpunitTester
     */
    protected $tester;

	/**
	 * @var Cookie
	 */
	private $cookie;

	/**
	 * @return Cookie
	 */
	public function getCookie(): Cookie {
		return $this->cookie->reveal();
	}

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @return Config
	 */
	public function getConfig(): Config {
		return $this->config->reveal();
	}

	/**
	 * @var \DOMDocument
	 */
	private $dom;

	/**
	 * @var \Prophecy\Prophet
	 */
	private $prophet;

	/**
	 * @var array
	 */
	private $options;

	public function setUp(): void
    {
		// before
		parent::setUp();

		// your set up methods here

		$this->prophet = new \Prophecy\Prophet;
		$this->cookie = $this->prophet->prophesize( Cookie::class );
		$this->config = $this->prophet->prophesize( Config::class );
		$this->dom = new \DOMDocument();

		$options['cookie_name'] = 'cookie';
		$options['active'] = true;
		$options['banner'] = 1;
		$options['url'] = "1";
		$options['anchor_text'] = "1";
		$options['button_text'] = "1";

		$options['text'] =
			<<<HTML
INFORMATIVA;
INFORMATIVA AI SENSI DEL D.LGS. n°196/03 E SS. MOD. E del REGOLAMENTO UE N°679/2016 (GDPR)
Benvenuto! Questo sito web utilizza cookies per le funzioni del negozio e assicurarti la migliore esperienza di navigazione.
Per maggiori informazioni consulta la pagina <a href="http://www.sitotest.com/shop/termini-e-condizioni/">Termini e Privacy</a>
<a href='http://www.sitotest.com/shop/termini-e-condizioni/'>Termini e Privacy</a>.

Proseguendo la navigazione sul sito, acconsenti all’uso dei cookies.
HTML;

		$options['text'] = $options['text']
			. "<script type='text/javascript'>alert('xss');</script>"
			. '<script type="text/javascript">alert("xss");</script>';

		$this->options = $options;
    }

    public function tearDown(): void
    {
        // Your tear down methods here.

        // Then...
        parent::tearDown();

		$this->prophet->checkPredictions();
    }

	protected function getInstance(): Cookie_Choices {
//		$sut = new Cookie_Choices( $this->getConfig(), $this->getCookie() );
		$sut = new Cookie_Choices( $this->options, $this->getCookie() );
		$this->assertInstanceOf( Cookie_Choices::class, $sut, '' );
		return $sut;
	}

    // Tests
//    public function test_it_works()
//    {
//        $post = static::factory()->post->create_and_get();
//
//        $this->assertInstanceOf(\WP_Post::class, $post);
//    }

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function itShouldBeInstantiatable() {
		$sut = $this->getInstance();
	}

	/**
	 * @test
	 * it should be REQUEST_URI_set
	 */
	public function itShouldBeREQUESTURISet() {
		$this->assertTrue( isset( $_SERVER["REQUEST_URI"] ) );
	}

	/**
	 * @test
	 * it should be method_exists
	 */
	public function itShouldExecute() {
		$this->getInstance()->run();
	}

	/**
	 * @test
	 * it should be method_exists
	 */
	public function itShouldOutput() {
		$this->getInstance()->print_script_inline();
		$output = $this->getActualOutput();
		codecept_debug($output);
	}

	/**
	 * @test
	 * it should be method_exists
	 */
	public function itShouldOutputBeCleaned() {
		$this->getInstance()->print_script_inline();
		$output = $this->getActualOutput();

		$this->assertNotEmpty( $output );
		$this->assertStringNotContainsString( "<script type='text/javascript'>alert('xss');</script>", $output );
		$this->assertStringContainsString( "alert('xss');alert(\"xss\");", $output );



		$this->assertStringNotContainsString( '<a href=\"http://www.sitotest.com/shop/termini-e-condizioni/\">', $output );
		$this->assertStringNotContainsString( '<a href=\"', $output );
	}

	/**
	 * @test
	 * it should be method_exists
	 */
	public function itShouldOutputBeCleanederthert() {
		$this->options['text'] = '<a href="http://www.sitotest.com/shop/termini-e-condizioni/">';

		$this->getInstance()->print_script_inline();
		$output = $this->getActualOutput();

		$this->assertStringContainsString( '<a href="http://www.sitotest.com/shop/termini-e-condizioni/">', $output );
	}

	/**
	 * @test
	 * it should be method_exists
	 */
	public function itShouldOutputBeCleanederthertrwe() {
		$this->options['text'] = "<a href='http://www.sitotest.com/shop/termini-e-condizioni/'>";

		$this->getInstance()->print_script_inline();
		$output = $this->getActualOutput();

		$this->assertStringContainsString( "<a href='http://www.sitotest.com/shop/termini-e-condizioni/'>", $output );
		$this->assertStringContainsString( '<a href=\'http://www.sitotest.com/shop/termini-e-condizioni/\'>', $output );
	}

	/**
	 * @test
	 * it should be content_erased
	 */
    public function it_should_be_content_erased() {

        $content = $this->getInstance()->AutoErase( '<body><script></script></body>' );

        $this->assertStringContainsString( "<body></body>", $content );

        codecept_debug($content);

//        $this->assertTrue( strpos( $content, 'cookieChoices.removeCookieConsent()') !== false, 'No banner found ' . $content );
//
//        $this->assertTrue( ! empty( $this->getInstance()->js_array ), 'Array empty');
//        $this->assertTrue( isset( $this->getInstance()->js_array[0] ), 'Array empty');

//        $this->dom->loadHTML( $this->banner->js_array[0] );
//        $this->assertNotEmpty( $this->dom->getElementsByTagName('script') );
    }
}
