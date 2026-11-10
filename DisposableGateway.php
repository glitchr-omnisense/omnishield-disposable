<?php

namespace Omnishield\Disposable;

use Omnisense\Contracts\Policy\DeclaresUsagePolicyInterface;
use Omnisense\Contracts\Policy\UsagePolicy;
use Omnishield\Model\Capabilities;
use Omnishield\Model\Identity;
use Omnishield\Model\Reputation;
use Omnishield\ReputationInterface;

/**
 * Whether an e-mail's domain is a disposable one: the site's allow list
 * first, then its deny list, then the list. Nothing leaves the site, nothing
 * is called: the list is a file.
 *
 * A disposable domain is known with full confidence - it is on a list, not
 * reported now and then: frequency 0, confidence 100, reasons [email,
 * disposable], and the part of the domain that matched in data.
 */
final class DisposableGateway implements ReputationInterface, DeclaresUsagePolicyInterface
{
    /** The usage policy its package declares (omnisense/contracts' schema). */
    public const POLICY = __DIR__.'/policy.json';

    private static ?UsagePolicy $policy = null;

    /** @var array<string, true> */
    private readonly array $allow;

    /** @var array<string, true> */
    private readonly array $deny;

    /**
     * @param list<string> $allow
     * @param list<string> $deny
     */
    public function __construct(private readonly DisposableList $list = new DisposableList(), array $allow = [], array $deny = [])
    {
        $this->allow = DisposableList::parse($allow);
        $this->deny = DisposableList::parse($deny);
    }

    public function getName(): string
    {
        return 'disposable';
    }

    public function getTitle(): string
    {
        return 'Disposable e-mail domains';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(reputation: true, thirdParty: false, cookies: false, reads: [Reputation::EMAIL], policy: $this->usagePolicy());
    }

    /** Its usage policy, as its package declares it: policy.json beside this class. */
    public function usagePolicy(): UsagePolicy
    {
        return self::$policy ??= UsagePolicy::fromFile(self::POLICY);
    }

    public function lookup(Identity $identity): Reputation
    {
        $domain = $identity->domain();
        if (null === $domain) {
            return Reputation::unknown();
        }
        if (null !== $allowed = DisposableList::find($this->allow, $domain)) {
            return Reputation::unknown(['domain' => $domain, 'allowed' => $allowed]);
        }
        $matched = DisposableList::find($this->deny, $domain);
        $source = null !== $matched ? 'deny' : 'list';
        $matched ??= $this->list->match($domain);
        if (null === $matched) {
            return Reputation::unknown(['domain' => $domain]);
        }

        return new Reputation(true, 0, 100.0, null, [Reputation::EMAIL, Reputation::DISPOSABLE], ['domain' => $domain, 'matched' => $matched, 'source' => $source]);
    }

    public function isDisposable(string $emailOrDomain): bool
    {
        return $this->lookup(new Identity(email: str_contains($emailOrDomain, '@') ? $emailOrDomain : '@'.$emailOrDomain))->known;
    }
}
