<?php

declare(strict_types=1);

namespace XtreamwayzTest\Expressive\Messenger;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Xtreamwayz\Expressive\Messenger\ConfigProvider;
use XtreamwayzTest\Expressive\Messenger\Fixtures\DummyCommand;
use XtreamwayzTest\Expressive\Messenger\Fixtures\DummyCommandHandler;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use function sprintf;

class CommandBusTest extends TestCase
{
    /** @var array */
    private $config;

    public function setUp() : void
    {
        $this->config = (new ConfigProvider())();
    }

    private function getContainer() : ServiceManager
    {
        $container = new ServiceManager();
        (new Config($this->config['dependencies']))->configureServiceManager($container);
        $container->setService('config', $this->config);

        return $container;
    }

    public function testItCanBeConstructed() : void
    {
        $container = $this->getContainer();

        /** @var MessageBus $commandBus */
        $commandBus = $container->get('messenger.bus.command');

        self::assertInstanceOf(MessageBusInterface::class, $commandBus);
        self::assertInstanceOf(MessageBus::class, $commandBus);
    }

    public function testItMustHaveOneCommandHandler() : void
    {
        $command = new DummyCommand();

        $this->expectException(NoHandlerForMessageException::class);
        $this->expectExceptionMessage(sprintf('No handler for message "%s"', DummyCommand::class));

        /** @var MessageBus $commandBus */
        $container  = $this->getContainer();
        $commandBus = $container->get('messenger.bus.command');
        $commandBus->dispatch($command);
    }

    public function testItCanHandleCommands() : void
    {
        $command = new DummyCommand();

        $commandHandler = $this->prophesize(DummyCommandHandler::class);
        $commandHandler->__invoke($command)->shouldBeCalled();

        // @codingStandardsIgnoreStart
        $this->config['dependencies']['services'][DummyCommandHandler::class]                         = $commandHandler->reveal();
        $this->config['messenger']['buses']['messenger.bus.command']['handlers'][DummyCommand::class] = DummyCommandHandler::class;
        // @codingStandardsIgnoreEnd

        /** @var MessageBus $commandBus */
        $container  = $this->getContainer();
        $commandBus = $container->get('messenger.bus.command');
        $commandBus->dispatch($command);
    }
}
