<?php

declare(strict_types=1);

namespace Laminas\Mvc\Controller;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\Stdlib\DispatchableInterface;
use Psr\Container\ContainerInterface;

use function get_class;
use function gettype;
use function is_object;
use function method_exists;
use function sprintf;

/**
 * Manager for loading controllers
 *
 * Does not define any controllers by default, but does add a validator.
 *
 * @extends AbstractPluginManager<DispatchableInterface>
 */
class ControllerManager extends AbstractPluginManager
{
    /**
     * We do not want arbitrary classes instantiated as controllers.
     *
     * @var bool
     */
    protected $autoAddInvokableClass = false;

    /**
     * Controllers must be of this type.
     *
     * @var class-string
     */
    protected $instanceOf = DispatchableInterface::class;

    /**
     * Constructor
     *
     * Injects an initializer for injecting controllers with an
     * event manager and plugin manager.
     *
     * @param  ConfigInterface|ContainerInterface $configOrContainerInstance
     * @param  array $config
     */
    public function __construct($configOrContainerInstance, array $config = [])
    {
        $this->addInitializer([$this, 'injectEventManager']);
        $this->addInitializer([$this, 'injectPluginManager']);
        parent::__construct($configOrContainerInstance, $config);
    }

    /**
     * Validate a plugin
     *
     * {@inheritDoc}
     */
    public function validate($instance): void
    {
        if (! $instance instanceof $this->instanceOf) {
            throw new InvalidServiceException(sprintf(
                'Plugin of type "%s" is invalid; must implement %s',
                is_object($instance) ? get_class($instance) : gettype($instance),
                $this->instanceOf
            ));
        }
    }

    /**
     * Initializer: inject EventManager instance
     *
     * If we have an event manager composed already, make sure it gets injected
     * with the shared event manager.
     *
     * The AbstractController lazy-instantiates an EM instance, which is why
     * the shared EM injection needs to happen; the conditional will always
     * pass.
     *
     * @param DispatchableInterface $controller
     */
    public function injectEventManager(ContainerInterface $container, $controller)
    {
        if (! $controller instanceof EventManagerAwareInterface) {
            return;
        }

        $events = $controller->getEventManager();
        if (! $events || ! $events->getSharedManager() instanceof SharedEventManagerInterface) {
            $controller->setEventManager($container->get('EventManager'));
        }
    }

    /**
     * Initializer: inject plugin manager
     *
     * @param DispatchableInterface $controller
     */
    public function injectPluginManager(ContainerInterface $container, $controller)
    {
        if (! method_exists($controller, 'setPluginManager')) {
            return;
        }

        $controller->setPluginManager($container->get('ControllerPluginManager'));
    }
}
