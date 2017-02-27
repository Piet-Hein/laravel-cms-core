<?php
namespace Czim\CmsCore\Test\Menu;

use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Contracts\Modules\Data\MenuPresenceInterface;
use Czim\CmsCore\Contracts\Modules\ModuleManagerInterface;
use Czim\CmsCore\Contracts\Modules\ModuleInterface;
use Czim\CmsCore\Contracts\Support\Data\MenuConfiguredModulesDataInterface;
use Czim\CmsCore\Menu\MenuModulesInterpreter;
use Czim\CmsCore\Support\Data\MenuPresence;
use Czim\CmsCore\Support\Enums\MenuPresenceMode;
use Czim\CmsCore\Support\Enums\MenuPresenceType;
use Czim\CmsCore\Test\TestCase;
use Illuminate\Support\Collection;

class MenuModulesInterpreterTest extends TestCase
{

    /**
     * Testing menu modules config.
     *
     * @var array
     */
    protected $menuModulesConfig = [];

    /**
     * @var Collection
     */
    protected $modules;


    /**
     * @test
     */
    function it_returns_standard_presences_if_no_menu_modules_are_configured()
    {
        $this->modules           = $this->getMockModules();
        $this->menuModulesConfig = [];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertInstanceof(MenuConfiguredModulesDataInterface::class, $data);
        static::assertEquals(['test-a', 'test-b'], $data->standard()->keys()->toArray(), 'Wrong module count, keys or order');
        static::assertInstanceOf(MenuPresenceInterface::class, head($data->standard()->get('test-a')));
        static::assertInstanceOf(MenuPresenceInterface::class, head($data->standard()->get('test-b')));
    }

    /**
     * @test
     */
    function it_returns_standard_presences_in_configured_ordering()
    {
        $this->modules = $this->getMockModules();

        $this->menuModulesConfig = [
            'test-b',
            'test-a',
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertInstanceof(MenuConfiguredModulesDataInterface::class, $data);
        static::assertEquals(['test-b', 'test-a'], $data->standard()->keys()->toArray(), 'Wrong module order (or keys array)');
        static::assertInstanceOf(MenuPresenceInterface::class, head($data->standard()->get('test-a')));
        static::assertInstanceOf(MenuPresenceInterface::class, head($data->standard()->get('test-b')));
    }

    /**
     * @test
     */
    function it_allows_overriding_of_module_presence_data_by_setting_values_in_module_configuration()
    {
        $this->modules = collect([
            'test-a' => $this->getMockModuleWithPresenceInstance(false),
        ]);

        $this->menuModulesConfig = [
            'test-a' => [
                // overrule presence to disable it
                'presence' => [
                    'id'     => 'test-overruled',
                    'type'   => MenuPresenceType::ACTION,
                    'label'  => 'something',
                    'action' => 'test',
                ],
            ],
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertInstanceOf(MenuPresenceInterface::class, head($data->standard()->first()));
        static::assertEquals('test-overruled', head($data->standard()->first())->id(), 'Presence should be overruled');
    }


    // ------------------------------------------------------------------------------
    //      Interpretation
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_interprets_module_presence_with_false_value()
    {
        $this->menuModulesConfig = [];

        $this->modules = collect([
            'test' => $this->getMockModuleWithCustomPresence(false),
        ]);

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertEmpty($data->standard());
    }

    /**
     * @test
     */
    function it_interprets_module_presence_with_null_value()
    {
        $this->menuModulesConfig = [];

        $this->modules = collect([
            'test' => $this->getMockModuleWithCustomPresence(null),
        ]);

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertEmpty($data->standard());
    }

    /**
     * @test
     */
    function it_interprets_module_presence_instance()
    {
        $this->menuModulesConfig = [];

        $this->modules = collect([
            'test-a' => $this->getMockModuleWithPresenceInstance(),
        ]);

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test-a'));
        static::assertInternalType('array', $data->standard()->get('test-a'));
        static::assertCount(1, $data->standard()->get('test-a'));
        static::assertInstanceOf(MenuPresenceInterface::class, head($data->standard()->get('test-a')));
        static::assertEquals('test-a', head($data->standard()->get('test-a'))->id());
    }

    /**
     * @test
     */
    function it_interprets_module_presence_array()
    {
        $this->menuModulesConfig = [];

        $this->modules = collect([
            'test' => $this->getMockModuleWithCustomPresence([
                'id'    => 'test',
                'type'  => MenuPresenceType::ACTION,
                'label' => 'something',
            ]),
        ]);

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test'));
        static::assertInternalType('array', $data->standard()->get('test'));
        static::assertCount(1, $data->standard()->get('test'));
        static::assertInstanceOf(MenuPresenceInterface::class, head($data->standard()->get('test')));
        static::assertEquals('test', head($data->standard()->get('test'))->id());
    }

    /**
     * @test
     */
    function it_interprets_module_presence_nested_arrays()
    {
        $this->menuModulesConfig = [];

        $this->modules = collect([
            'test' => $this->getMockModuleWithCustomPresence([
                [
                    'id'    => 'test',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something',
                ],
                [
                    'id'    => 'test-2',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something else',
                ],
            ]),
        ]);

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test'));
        static::assertInternalType('array', $data->standard()->get('test'));
        static::assertCount(2, $data->standard()->get('test'));

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test')[0]);
        static::assertEquals('test', $data->standard()->get('test')[0]->id());

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test')[1]);
        static::assertEquals('test-2', $data->standard()->get('test')[1]->id());
        static::assertEquals('something else', $data->standard()->get('test')[1]->label());
    }

    /**
     * @test
     */
    function it_interprets_module_presence_nested_group_arrays()
    {
        $this->menuModulesConfig = [];

        $this->modules = collect([
            'test' => $this->getMockModuleWithCustomPresence([
                [
                    'id'    => 'test',
                    'type'  => MenuPresenceType::GROUP,
                    'label' => 'something',
                    'children' => [
                        [
                            'id'    => 'test-child',
                            'type'  => MenuPresenceType::ACTION,
                            'label' => 'something else',
                        ]
                    ]
                ]
            ]),
        ]);

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test'));
        static::assertInternalType('array', $data->standard()->get('test'));
        static::assertCount(1, $data->standard()->get('test'));

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test')[0]);
        static::assertEquals('test', $data->standard()->get('test')[0]->id());

        static::assertCount(1, $data->standard()->get('test')[0]->children());
        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test')[0]->children()[0]);
        static::assertEquals('test-child', $data->standard()->get('test')[0]->children()[0]->id());
    }

    /**
     * @test
     */
    function it_interprets_module_presence_nested_group_array_in_instance()
    {
        $this->menuModulesConfig = [];

        $this->modules = collect([
            'test' => $this->getMockModuleWithCustomPresence(
                new MenuPresence([
                    'id'    => 'test',
                    'type'  => MenuPresenceType::GROUP,
                    'label' => 'something',
                    'children' => [
                        [
                            'id'    => 'test-child',
                            'type'  => MenuPresenceType::ACTION,
                            'label' => 'something else',
                        ]
                    ]
                ])
            ),
        ]);

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test'));
        static::assertInternalType('array', $data->standard()->get('test'));
        static::assertCount(1, $data->standard()->get('test'));

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test')[0]);
        static::assertEquals('test', $data->standard()->get('test')[0]->id());

        static::assertCount(1, $data->standard()->get('test')[0]->children());
        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test')[0]->children()[0]);
        static::assertEquals('test-child', $data->standard()->get('test')[0]->children()[0]->id());
    }


    // ------------------------------------------------------------------------------
    //      Configuration interpretation
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_allows_disabling_a_menu_presence()
    {
        $this->modules = collect([
            'test-a' => $this->getMockModuleWithPresenceInstance(false),
        ]);

        $this->menuModulesConfig = [
            'test-a' => [
                'mode' => MenuPresenceMode::DELETE,
            ],
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertEmpty($data->standard());
    }

    /**
     * @test
     */
    function it_allows_disabling_a_menu_presence_with_top_level_shorthand()
    {
        $this->modules = collect([
            'test-a' => $this->getMockModuleWithPresenceInstance(false),
        ]);

        $this->menuModulesConfig = [
            'test-a' => false,
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertEmpty($data->standard());
    }

    /**
     * @test
     */
    function it_allows_disabling_all_menu_presences_with_top_level_shorthand()
    {
        $this->modules = collect([
            'test' => $this->getMockModuleWithCustomPresence([
                [
                    'id'    => 'test-1',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something',
                ],
                [
                    'id'    => 'test-2',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something else',
                ],
            ]),
        ]);

        $this->menuModulesConfig = [
            'test' => false,
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertEmpty($data->standard());
    }

    /**
     * @test
     */
    function it_allows_disabling_one_of_multiple_menu_presences_selectively()
    {
        $this->modules = collect([
            'test' => $this->getMockModuleWithCustomPresence([
                [
                    'id'    => 'test-1',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something',
                ],
                [
                    'id'    => 'test-2',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something else',
                ],
            ]),
        ]);

        $this->menuModulesConfig = [
            'test' => [
                [
                    'mode' => MenuPresenceMode::DELETE,
                ],
            ],
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test'));
        static::assertCount(1, $data->standard()->get('test'));
        static::assertInstanceOf(MenuPresenceInterface::class, head($data->standard()->get('test')));
        static::assertEquals('test-2', head($data->standard()->get('test'))->id());
    }

    /**
     * @test
     */
    function it_allows_disabling_one_of_multiple_menu_presences_selectively_with_a_false_value()
    {
        $this->modules = collect([
            'test' => $this->getMockModuleWithCustomPresence([
                [
                    'id'    => 'test-1',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something',
                ],
                [
                    'id'    => 'test-2',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something else',
                ],
            ]),
        ]);

        $this->menuModulesConfig = [
            'test' => [
                [],
                false,
            ],
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test'));
        static::assertCount(1, $data->standard()->get('test'));
        static::assertInstanceOf(MenuPresenceInterface::class, head($data->standard()->get('test')));
        static::assertEquals('test-1', head($data->standard()->get('test'))->id());
    }

    /**
     * @test
     */
    function it_allows_adding_a_menu_presence()
    {
        $this->modules = collect([
            'test-a' => $this->getMockModuleWithPresenceInstance(false),
        ]);

        $this->menuModulesConfig = [
            'test-a' => [
                'mode' => MenuPresenceMode::ADD,
                'id'    => 'test-added',
                'type'  => MenuPresenceType::ACTION,
                'label' => 'something',
            ],
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test-a'));
        static::assertCount(2, $data->standard()->get('test-a'));

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test-a')[0]);
        static::assertEquals('test-a', $data->standard()->get('test-a')[0]->id());

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test-a')[1]);
        static::assertEquals('test-added', $data->standard()->get('test-a')[1]->id());
    }

    /**
     * @test
     */
    function it_allows_adding_a_menu_presence_in_combination_with_deleting_another()
    {
        $this->modules = collect([
            'test-a' => $this->getMockModuleWithPresenceInstance(false),
        ]);

        $this->menuModulesConfig = [
            'test-a' => [
                [
                    'mode' => MenuPresenceMode::DELETE,
                ],
                [
                    'mode' => MenuPresenceMode::ADD,
                    'id'    => 'test-added',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something',
                ]
            ],
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test-a'));
        static::assertCount(1, $data->standard()->get('test-a'));

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test-a')[0]);
        static::assertEquals('test-added', $data->standard()->get('test-a')[0]->id());
    }

    /**
     * @test
     */
    function it_allows_modifying_a_menu_presence_as_default_mode()
    {
        $this->modules = collect([
            'test-a' => $this->getMockModuleWithPresenceInstance(false),
        ]);

        $this->menuModulesConfig = [
            'test-a' => [
                'id' => 'test-modified',
            ],
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test-a'));
        static::assertCount(1, $data->standard()->get('test-a'));

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test-a')[0]);
        static::assertEquals('test-modified', $data->standard()->get('test-a')[0]->id(), 'Not modified');
        static::assertEquals('something', $data->standard()->get('test-a')[0]->label(), 'Original value missing');
    }

    /**
     * @test
     */
    function it_allows_modifying_a_menu_presence_explicitly()
    {
        $this->modules = collect([
            'test-a' => $this->getMockModuleWithPresenceInstance(false),
        ]);

        $this->menuModulesConfig = [
            'test-a' => [
                'mode'  => MenuPresenceMode::MODIFY,
                'label' => 'modified label',
            ],
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test-a'));
        static::assertCount(1, $data->standard()->get('test-a'));

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test-a')[0]);
        static::assertEquals('test-a', $data->standard()->get('test-a')[0]->id(), 'Original value missing');
        static::assertEquals('modified label', $data->standard()->get('test-a')[0]->label(), 'Not modified');
    }

    /**
     * @test
     */
    function it_allows_replacing_a_menu_presence()
    {
        $this->modules = collect([
            'test-b' => $this->getMockModuleWithPermissions(),
        ]);

        $this->menuModulesConfig = [
            'test-b' => [
                'mode'   => MenuPresenceMode::REPLACE,
                'type'   => MenuPresenceType::LINK,
                'label'  => 'replaced label',
                'action' => 'new:action',
            ],
        ];

        $interpreter = $this->makeModulesInterpreter();

        $data = $interpreter->interpret();

        static::assertCount(1, $data->standard());
        static::assertTrue($data->standard()->has('test-b'));
        static::assertCount(1, $data->standard()->get('test-b'));

        static::assertInstanceOf(MenuPresenceInterface::class, $data->standard()->get('test-b')[0]);
        static::assertEquals(MenuPresenceType::LINK, $data->standard()->get('test-b')[0]->type());
        static::assertEquals('replaced label', $data->standard()->get('test-b')[0]->label());
        static::assertNull($data->standard()->get('test-b')[0]->id(), 'Original value still present');
        static::assertEmpty($data->standard()->get('test-b')[0]->permissions(), 'Original value still present');

    }


    // ------------------------------------------------------------------------------
    //      Helpers
    // ------------------------------------------------------------------------------

    /**
     * @return CoreInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockCore()
    {
        $mock = $this->getMockBuilder(CoreInterface::class)->getMock();

        $mock->method('modules')
            ->willReturn($this->getMockModuleManager($this->modules));

        $mock->method('moduleConfig')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'menu.modules':
                        return $this->menuModulesConfig;
                }

                return null;
            });

        return $mock;
    }

    /**
     * @param null|Collection $modules
     * @return ModuleManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockModuleManager($modules = null)
    {
        $mock = $this->getMockBuilder(ModuleManagerInterface::class)->getMock();

        $modules = $modules ?: new Collection;

        $mock->expects(static::once())
            ->method('getModules')
            ->willReturn($modules);

        return $mock;
    }

    /**
     * @return Collection
     */
    protected function getMockModules()
    {
        return new Collection([
            'test-a' => $this->getMockModuleWithPresenceInstance(),
            'test-b' => $this->getMockModuleWithPermissions(),
        ]);
    }

    /**
     * @param bool $expected    whether the menu presence call is expected
     * @return \PHPUnit_Framework_MockObject_MockObject|ModuleInterface
     */
    protected function getMockModuleWithPresenceInstance($expected = true)
    {
        $mock = $this->getMockBuilder(ModuleInterface::class)->getMock();

        $mock->method('getKey')->willReturn('test-a');

        $mock->expects($expected ? static::once() : static::any())
            ->method('getMenuPresence')
            ->willReturn(
                new MenuPresence([
                    'id'    => 'test-a',
                    'type'  => MenuPresenceType::ACTION,
                    'label' => 'something',
                ])
            );

        return $mock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ModuleInterface
     */
    protected function getMockModuleWithPermissions()
    {
        $mock = $this->getMockBuilder(ModuleInterface::class)->getMock();

        $mock->method('getKey')->willReturn('test-b');

        $mock->expects(static::once())
            ->method('getMenuPresence')
            ->willReturn([
                'id'          => 'test-b',
                'type'        => MenuPresenceType::ACTION,
                'label'       => 'something',
                'action'      => 'test',
                'permissions' => ['can.do.something', 'can.do.more'],
            ]);

        return $mock;
    }

    /**
     * @param mixed $presence
     * @return \PHPUnit_Framework_MockObject_MockObject|ModuleInterface
     */
    protected function getMockModuleWithCustomPresence($presence)
    {
        $mock = $this->getMockBuilder(ModuleInterface::class)->getMock();

        $mock->method('getKey')->willReturn('test');

        $mock->method('getMenuPresence')
             ->willReturn($presence);

        return $mock;
    }

    /**
     * @return MenuModulesInterpreter
     */
    protected function makeModulesInterpreter()
    {
        return new MenuModulesInterpreter($this->getMockCore());
    }

}

