<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Container class.
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBindSingleton(): void
    {
        $this->container->singleton(
            TestService::class,
            function () {
                return new TestService('singleton');
            }
        );

        $service1 = $this->container->get(TestService::class);
        $service2 = $this->container->get(TestService::class);

        $this->assertInstanceOf(TestService::class, $service1);
        $this->assertSame($service1, $service2);
        $this->assertEquals('singleton', $service1->getValue());
    }

    public function testBindTransient(): void
    {
        $this->container->transient(
            TestService::class,
            function () {
                return new TestService('transient');
            }
        );

        $service1 = $this->container->get(TestService::class);
        $service2 = $this->container->get(TestService::class);

        $this->assertInstanceOf(TestService::class, $service1);
        $this->assertNotSame($service1, $service2);
        $this->assertEquals('transient', $service1->getValue());
    }

    public function testBindInstance(): void
    {
        $instance = new TestService('instance');
        $this->container->instance(TestService::class, $instance);

        $service = $this->container->get(TestService::class);
        $this->assertSame($instance, $service);
        $this->assertEquals('instance', $service->getValue());
    }

    public function testBindClassName(): void
    {
        $this->container->bind(TestService::class, TestService::class);

        $service = $this->container->get(TestService::class);
        $this->assertInstanceOf(TestService::class, $service);
    }

    public function testBindInterfaceToImplementation(): void
    {
        $this->container->bind(
            TestInterface::class,
            TestImplementation::class
        );

        $service = $this->container->get(TestInterface::class);
        $this->assertInstanceOf(TestImplementation::class, $service);
        $this->assertInstanceOf(TestInterface::class, $service);
    }

    public function testHasService(): void
    {
        $this->assertFalse($this->container->has(TestService::class));

        $this->container->bind(TestService::class, TestService::class);
        $this->assertTrue($this->container->has(TestService::class));
    }

    public function testGetNonExistentServiceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->container->get(NonExistentService::class);
    }

    public function testResolveSimpleClass(): void
    {
        $service = $this->container->resolve(TestService::class);
        $this->assertInstanceOf(TestService::class, $service);
    }

    public function testResolveClassWithDependencies(): void
    {
        $dependency = new TestDependency('dependency');
        $this->container->instance(TestDependency::class, $dependency);

        $service = $this->container->resolve(ServiceWithDependency::class);
        $this->assertInstanceOf(ServiceWithDependency::class, $service);
        $this->assertSame($dependency, $service->getDependency());
    }

    public function testResolveClassWithMultipleDependencies(): void
    {
        $dep1 = new TestDependency('dep1');
        $dep2 = new TestDependency('dep2');
        
        $this->container->instance(TestDependency::class, $dep1);
        $this->container->bind(AnotherDependency::class, function () use ($dep2) {
            return $dep2;
        });

        $service = $this->container->resolve(ServiceWithMultipleDependencies::class);
        $this->assertInstanceOf(ServiceWithMultipleDependencies::class, $service);
    }

    public function testResolveClassWithoutConstructor(): void
    {
        $service = $this->container->resolve(NoConstructorService::class);
        $this->assertInstanceOf(NoConstructorService::class, $service);
    }

    public function testResolveNonInstantiableClassThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->container->resolve(AbstractService::class);
    }

    public function testCallClosure(): void
    {
        $result = $this->container->call(function (TestService $service) {
            return $service->getValue();
        });

        // This should work because TestService can be auto-resolved
        $this->assertEquals('default', $result);
    }

    public function testCallMethodOnObject(): void
    {
        $service = new TestService('test');
        $result = $this->container->call([$service, 'getValue']);
        $this->assertEquals('test', $result);
    }

    public function testCallStaticMethod(): void
    {
        $result = $this->container->call([StaticService::class, 'getValue']);
        $this->assertEquals('static', $result);
    }

    public function testCallWithParameters(): void
    {
        $result = $this->container->call(
            function (string $name, int $age) {
                return "{$name} is {$age} years old";
            },
            ['name' => 'John', 'age' => 30]
        );

        $this->assertEquals('John is 30 years old', $result);
    }

    public function testCallWithMixedParameters(): void
    {
        $this->container->bind(TestService::class, function () {
            return new TestService('injected');
        });

        $result = $this->container->call(
            function (TestService $service, string $name) {
                return $service->getValue() . '-' . $name;
            },
            ['name' => 'test']
        );

        $this->assertEquals('injected-test', $result);
    }

    public function testClearContainer(): void
    {
        $this->container->bind(TestService::class, TestService::class);
        $this->assertTrue($this->container->has(TestService::class));

        $this->container->clear();
        $this->assertFalse($this->container->has(TestService::class));
    }

    public function testGetBindings(): void
    {
        $this->container->bind(TestService::class, TestService::class);
        $bindings = $this->container->getBindings();
        $this->assertArrayHasKey(TestService::class, $bindings);
    }

    public function testGetSingletons(): void
    {
        $this->container->singleton(TestService::class, TestService::class);
        $this->container->get(TestService::class);

        $singletons = $this->container->getSingletons();
        $this->assertArrayHasKey(TestService::class, $singletons);
    }

    public function testBindWithClosureFactory(): void
    {
        $counter = 0;
        
        $this->container->bind(
            TestService::class,
            function () use (&$counter) {
                $counter++;
                return new TestService("call-{$counter}");
            },
            false // transient
        );

        $service1 = $this->container->get(TestService::class);
        $service2 = $this->container->get(TestService::class);

        $this->assertEquals('call-1', $service1->getValue());
        $this->assertEquals('call-2', $service2->getValue());
        $this->assertEquals(2, $counter);
    }

    public function testSingletonWithClosureFactory(): void
    {
        $counter = 0;
        
        $this->container->bind(
            TestService::class,
            function () use (&$counter) {
                $counter++;
                return new TestService("call-{$counter}");
            },
            true // singleton
        );

        $service1 = $this->container->get(TestService::class);
        $service2 = $this->container->get(TestService::class);

        $this->assertEquals('call-1', $service1->getValue());
        $this->assertEquals('call-1', $service2->getValue());
        $this->assertSame($service1, $service2);
        $this->assertEquals(1, $counter);
    }

    public function testResolveWithDefaultValues(): void
    {
        $service = $this->container->resolve(ServiceWithDefaults::class);
        $this->assertInstanceOf(ServiceWithDefaults::class, $service);
        $this->assertEquals('default', $service->getValue());
    }

    public function testResolveWithOptionalDependencies(): void
    {
        $service = $this->container->resolve(ServiceWithOptionalDependency::class);
        $this->assertInstanceOf(ServiceWithOptionalDependency::class, $service);
    }
}

// Test classes

interface TestInterface
{
    public function getValue(): string;
}

class TestImplementation implements TestInterface
{
    public function getValue(): string
    {
        return 'implementation';
    }
}

class TestService
{
    private string $value;

    public function __construct(string $value = 'default')
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

class NonExistentService
{
    // This class is not registered in the container
}

class TestDependency
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

class ServiceWithDependency
{
    private TestDependency $dependency;

    public function __construct(TestDependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency(): TestDependency
    {
        return $this->dependency;
    }
}

class AnotherDependency
{
    public function getValue(): string
    {
        return 'another';
    }
}

class ServiceWithMultipleDependencies
{
    public function __construct(
        private TestDependency $dep1,
        private AnotherDependency $dep2
    ) {
    }

    public function getDep1(): TestDependency
    {
        return $this->dep1;
    }

    public function getDep2(): AnotherDependency
    {
        return $this->dep2;
    }
}

class NoConstructorService
{
    public function getValue(): string
    {
        return 'no-constructor';
    }
}

abstract class AbstractService
{
    abstract public function doSomething();
}

class StaticService
{
    public static function getValue(): string
    {
        return 'static';
    }
}

class ServiceWithDefaults
{
    private string $value;

    public function __construct(string $value = 'default')
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

class ServiceWithOptionalDependency
{
    public function __construct(
        private ?TestDependency $dependency = null
    ) {
    }

    public function getDependency(): ?TestDependency
    {
        return $this->dependency;
    }
}
