<?php
declare(strict_types=1);

class CookieChoicesTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    protected function getInstance() {
    	$sut = new \Italy_Cookie_Choices\Core\Cookie_Choices( new \ItalyStrap\Config\Config(), new \Overclokk\Cookie\Cookie() );
    	$this->assertInstanceOf( \Italy_Cookie_Choices\Core\Cookie_Choices::class, $sut, '' );
    	return $sut;
	}

	/**
	 * @test
	 */
	public function instanceOk() {
		$sut = $this->getInstance();
	}

	/**
	 * @test
	 */
	public function bannerOutput() {
		$sut = $this->getInstance();
		\ob_start();
		$sut->print_script_inline();
		$banner = \ob_get_clean();

		codecept_debug( $banner );
	}
}