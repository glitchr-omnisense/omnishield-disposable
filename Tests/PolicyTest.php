<?php

namespace Omnishield\Disposable\Tests;

use Omnisense\Contracts\Policy\UsagePolicy;
use Omnishield\Disposable\DisposableGateway;
use Omnishield\Disposable\DisposableGatewayFactory;
use Omnishield\Registry;
use PHPUnit\Framework\TestCase;

/** Its usage policy: declared as data, read by the gateway, enforced through the registry. */
final class PolicyTest extends TestCase
{
    public function testThePackageDeclaresItsPolicy(): void
    {
        $policy = UsagePolicy::fromFile(DisposableGateway::POLICY);
        $gateway = (new DisposableGatewayFactory())->create([]);

        self::assertSame('disposable', $policy->provider, 'named after the factory');
        self::assertSame('2026-11-10', $policy->provenance->verifiedAt);
        self::assertSame('allowed', $policy->rights->commercial->value);
        self::assertSame([], $policy->rateLimits, 'no limit');
        self::assertEquals($policy, $gateway->capabilities()->policy());
        self::assertMatchesRegularExpression('~"omnisense": \{\s*"policy": "policy\.json"~', (string) file_get_contents(__DIR__.'/../composer.json'), 'composer.json names it');
    }
}
