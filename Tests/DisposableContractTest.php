<?php

namespace Omnishield\Disposable\Tests;

use Omnishield\Disposable\DisposableGatewayFactory;
use Omnishield\GatewayFactoryInterface;
use Omnishield\Model\Identity;
use Omnishield\Testing\GatewayContractTests;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** The family's contract, on the list shipped in the package: nothing is asked of anyone. */
final class DisposableContractTest extends TestCase
{
    use GatewayContractTests;

    protected function factory(HttpClientInterface $http): GatewayFactoryInterface
    {
        return new DisposableGatewayFactory();
    }

    protected function options(): array
    {
        return [];
    }

    protected function recordedCases(): iterable
    {
        yield 'a disposable domain' => [new Identity('192.0.2.10', 'someone@mailinator.com', 'Camille'), false];
        yield 'a domain of its own' => [new Identity('192.0.2.10', 'camille@example.org', 'Camille'), true];
        yield 'a domain denied by the site' => [new Identity(email: 'a@mx.throwaway.example'), false, [], ['deny' => 'throwaway.example']];
    }
}
