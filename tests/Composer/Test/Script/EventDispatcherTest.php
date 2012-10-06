<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Test\Script;

use Composer\Test\TestCase;
use Composer\Script\Event;
use Composer\Script\EventDispatcher;

class EventDispatcherTest extends TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testListenerExceptionsAreCaught()
    {
        $io = $this->getMock('Composer\IO\IOInterface');
        $dispatcher = $this->getDispatcherStubForListenersTest(array(
            "Composer\Test\Script\EventDispatcherTest::call"
        ), $io);

        $io->expects($this->once())
            ->method('write')
            ->with('<error>Script Composer\Test\Script\EventDispatcherTest::call handling the post-install-cmd event terminated with an exception</error>');

        $dispatcher->dispatchCommandEvent("post-install-cmd");
    }

    public function testDispatcherCanExecuteCommandLineScripts()
    {
        $eventCliCommand = 'phpunit';

        $process = $this->getMock('Composer\Util\ProcessExecutor');
        $dispatcher = $this->getMockBuilder('Composer\Script\EventDispatcher')
            ->setConstructorArgs(array(
                $this->getMock('Composer\Composer'),
                $this->getMock('Composer\IO\IOInterface'),
                $process,
            ))
            ->setMethods(array('getListeners'))
            ->getMock();

        $listeners = array($eventCliCommand);
        $dispatcher->expects($this->atLeastOnce())
            ->method('getListeners')
            ->will($this->returnValue($listeners));

        $process->expects($this->once())
            ->method('execute')
            ->with($eventCliCommand);

        $dispatcher->dispatchCommandEvent("post-install-cmd");
    }

    public function testDispatcherCanWriteSuccessfulOutputToEventIO()
    {
        $eventCliCommand = 'phpunit';
        $testCommandOutput = 'Test command output';

        // ConsoleIO, which will eventually have Event output written to it
        $consoleIO = $this->getMockBuilder('Composer\IO\ConsoleIO')
            ->disableOriginalConstructor()
            ->setMethods(array('write'))
            ->getMock();
        // THIS DOESNT WORK YET!
        $consoleIO->expects($this->once())
            ->method('write')
            ->with($testCommandOutput);

        // ProcessExecutor, which will execute "phpunit"
        $process = $this->getMock('Composer\Util\ProcessExecutor');
        $dispatcher = $this->getMockBuilder('Composer\Script\EventDispatcher')
            ->setConstructorArgs(array(
                $this->getMock('Composer\Composer'),
                $consoleIO,
                $process,
            ))
            ->setMethods(array('getListeners'))
            ->getMock();

        $listeners = array($eventCliCommand);
        $dispatcher->expects($this->atLeastOnce())
            ->method('getListeners')
            ->will($this->returnValue($listeners));

        $process->expects($this->once())
            ->method('execute')
            ->with($eventCliCommand);

        $dispatcher->dispatchCommandEvent("post-install-cmd");
    }

    private function getDispatcherStubForListenersTest($listeners, $io)
    {
        $dispatcher = $this->getMockBuilder('Composer\Script\EventDispatcher')
            ->setConstructorArgs(array(
                $this->getMock('Composer\Composer'),
                $io,
            ))
            ->setMethods(array('getListeners'))
            ->getMock();

        $dispatcher->expects($this->atLeastOnce())
            ->method('getListeners')
            ->will($this->returnValue($listeners));

        return $dispatcher;
    }

    public static function call()
    {
        throw new \RuntimeException();
    }
}
