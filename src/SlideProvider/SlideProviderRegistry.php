<?php

/*
 * Copyright MADE/YOUR/DAY OG <mail@madeyourday.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MadeYourDay\RockSolidSlider\SlideProvider;

/**
 * The registry for slide providers.
 *
 * @author Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class SlideProviderRegistry
{
    /**
     * Registered providers by type as key and instance as value.
     *
     * @var SlideProviderInterface[]
     */
    private $providers = [];

    /**
     * Create a new instance.
     *
     * @param SlideProviderInterface[] $providers
     */
    public function __construct(array $providers = [])
    {
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    /**
     * Add a provider.
     *
     * @param SlideProviderInterface $provider The provider to add.
     *
     * @return void
     */
    public function addProvider(SlideProviderInterface $provider)
    {
        $this->providers[(string) $provider->getName()] = $provider;
    }

    /**
     * Retrieve the provider by name.
     *
     * @param string $name The provider to retrieve.
     *
     * @return bool
     */
    public function hasProvider($name)
    {
        return isset($this->providers[(string) $name]);
    }

    /**
     * Retrieve the provider by name.
     *
     * @param string $name The provider to retrieve.
     *
     * @return SlideProviderInterface
     */
    public function getProvider($name)
    {
        if (!$this->hasProvider($name)) {
            throw new \InvalidArgumentException('No provider with the name ' . $name);
        }

        return $this->providers[(string) $name];
    }
}
