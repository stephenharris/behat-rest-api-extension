<?php
namespace StephenHarris\RestApiExtension\ServiceContainer;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Behat\Testwork\ServiceContainer\ServiceProcessor;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use InvalidArgumentException;
use RuntimeException;

/**
 * Main part of the Behat extension.
 */
class RestApiExtension implements ExtensionInterface
{
    /**
     * @var ServiceProcessor
     */
    protected $processor;

    /**
     * Constructor.
     *
     * @param ServiceProcessor|null $processor Optional.
     */
    public function __construct(ServiceProcessor $processor = null)
    {
        $this->processor = $processor ?: new ServiceProcessor();
    }

    /**
     * Returns the extension config key.
     *
     * @return string
     */
    public function getConfigKey()
    {
        return 'restapi';
    }

    /**
     * Initialise extension.
     *
     * This method is called immediately after all extensions are activated but
     * before any extension `configure()` method is called. This allows extensions
     * to hook into the configuration of other extensions providing such an
     * extension point.
     *
     * @param ExtensionManager $extension_manager
     */
    public function initialize(ExtensionManager $extension_manager)
    {
    }

    /**
     * Declare configuration options for the extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
              ->scalarNode('endpoint')->end()
              ->arrayNode('oauth1')
                  ->addDefaultsIfNotSet()
                  ->children()
                      ->scalarNode('client_key')->end()
                      ->scalarNode('client_secret')->end()
                      ->scalarNode('token_key')->end()
                      ->scalarNode('token_secret')->end()
                  ->end()
              ->end()
            ->end()
        ->end();
    }

    /**
     * Load extension services into ServiceContainer.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');

                $container->setParameter('restapi.parameters', $config);
    }

    /**
     * Modify the container before Symfony compiles it.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
    }
}
