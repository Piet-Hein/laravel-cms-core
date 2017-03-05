<?php
namespace Czim\CmsCore\Test\Support\Translation;

use Czim\CmsCore\Support\Translation\CmsTranslator;
use Czim\CmsCore\Test\TestCase;
use Illuminate\Translation\LoaderInterface;

class CmsTranslatorTest extends TestCase
{

    /**
     * @test
     */
    function it_falls_back_to_application_translation_if_cms_translation_key_not_found()
    {
        /** @var LoaderInterface|\PHPUnit_Framework_MockObject_MockObject $loaderMock */
        $loaderMock = $this->getMockBuilder(LoaderInterface::class)->getMock();

        $translator = new CmsTranslator($loaderMock, 'en');

        $translator->addLines(['testing.test' => 'exists application'], 'en', '*');

        static::assertEquals('exists application', $translator->get('testing.test'));
    }

    /**
     * @test
     */
    function it_does_not_fall_back_to_application_translation_if_key_already_prefixed()
    {
        /** @var LoaderInterface|\PHPUnit_Framework_MockObject_MockObject $loaderMock */
        $loaderMock = $this->getMockBuilder(LoaderInterface::class)->getMock();

        $translator = new CmsTranslator($loaderMock, 'en');

        $translator->addLines(['testing.test' => 'exists application'], 'en', '*');

        static::assertEquals('cms::testing.test', $translator->get('cms::testing.test'));
    }

    /**
     * @test
     */
    function it_prefixes_a_translation_key_using_cms_translation_if_available()
    {
        /** @var LoaderInterface|\PHPUnit_Framework_MockObject_MockObject $loaderMock */
        $loaderMock = $this->getMockBuilder(LoaderInterface::class)->getMock();

        $translator = new CmsTranslator($loaderMock, 'en');

        $translator->addLines(['testing.test' => 'exists cms'], 'en', 'cms');
        $translator->addLines(['testing.test' => 'exists application'], 'en', '*');

        static::assertEquals('exists cms', $translator->get('testing.test'));
    }

}
