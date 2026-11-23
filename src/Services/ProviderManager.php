<?php

declare(strict_types=1);

namespace NaijaTel\Services;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory;
use NaijaTel\Contracts\MnpProviderInterface;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Exceptions\ConfigurationException;
use NaijaTel\Exceptions\ProviderException;
use NaijaTel\Providers\FakeMnpProvider;
use NaijaTel\Providers\Icn\IcnMnpProvider;
use NaijaTel\Support\NetworkPrefixRepository;

class ProviderManager
{
    /**
     * @var array<string, MnpProviderInterface>
     */
    private array $resolvedProviders = [];

    /**
     * @var array<string, Closure(Container): MnpProviderInterface|string>
     */
    private array $customCreators = [];

    public function __construct(
        private readonly Container $container
    ) {}

    /**
     * Get a provider instance by name, or default provider if omitted.
     *
     * @throws ProviderException
     * @throws ConfigurationException
     */
    public function provider(?string $name = null): MnpProviderInterface
    {
        $name = $name ?? $this->getDefaultProvider();

        if (isset($this->resolvedProviders[$name])) {
            return $this->resolvedProviders[$name];
        }

        return $this->resolvedProviders[$name] = $this->resolve($name);
    }

    /**
     * Register a custom provider creator.
     *
     * @param  Closure(Container): MnpProviderInterface|class-string<MnpProviderInterface>  $creator
     */
    public function extend(string $name, Closure|string $creator): self
    {
        $this->customCreators[$name] = $creator;
        unset($this->resolvedProviders[$name]);

        return $this;
    }

    /**
     * Resolve a provider by name.
     *
     * @throws ProviderException
     */
    protected function resolve(string $name): MnpProviderInterface
    {
        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($name);
        }

        return match ($name) {
            'fake' => $this->container->make(FakeMnpProvider::class),
            'icn' => $this->createIcnProvider(),
            default => throw new ProviderException("MNP provider [{$name}] is not supported or registered.", $name),
        };
    }

    /**
     * Call custom creator callback or instantiate class.
     */
    protected function callCustomCreator(string $name): MnpProviderInterface
    {
        $creator = $this->customCreators[$name];

        if (is_string($creator) && class_exists($creator)) {
            $instance = $this->container->make($creator);
        } elseif ($creator instanceof Closure) {
            $instance = $creator($this->container);
        } else {
            throw new ProviderException("Invalid creator for custom provider [{$name}].", $name);
        }

        if (! $instance instanceof MnpProviderInterface) {
            throw new ProviderException(
                "Custom provider [{$name}] must implement ".MnpProviderInterface::class,
                $name
            );
        }

        return $instance;
    }

    /**
     * Instantiate ICN provider using configuration.
     */
    protected function createIcnProvider(): IcnMnpProvider
    {
        $config = (array) $this->container->make('config')->get('naija-tel.providers.icn', []);

        return new IcnMnpProvider(
            http: $this->container->make(Factory::class),
            prefixRepository: $this->container->make(NetworkPrefixRepository::class),
            normalizer: $this->container->make(PhoneNumberParserInterface::class),
            config: $config
        );
    }

    /**
     * Get default provider name from config.
     */
    public function getDefaultProvider(): string
    {
        return (string) $this->container->make('config')->get('naija-tel.default_provider', 'fake');
    }
}
