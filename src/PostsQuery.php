<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic;

use DateTimeImmutable;
use Tigusigalpa\CryptoPanic\Enums\Filter;
use Tigusigalpa\CryptoPanic\Enums\Kind;
use Tigusigalpa\CryptoPanic\Enums\PanicPeriod;
use Tigusigalpa\CryptoPanic\Enums\PanicSort;
use Tigusigalpa\CryptoPanic\Exceptions\ValidationException;

/**
 * Immutable value object for the GET /posts/ query parameters.
 *
 * Authentication is configured on the client, not in the query object.
 * Only clear client-side syntax and cross-field rules are validated;
 * the CryptoPanic server remains the authority for plan eligibility
 * and evolving allowed values.
 */
final class PostsQuery
{
    /**
     * @param list<string>      $currencies
     * @param Filter|string|null $filter      Known filter or custom string.
     * @param Kind|string|null  $kind        Known kind or custom string.
     * @param PanicPeriod|string|null $panicPeriod
     * @param PanicSort|string|null $panicSort
     */
    public function __construct(
        public readonly ?bool $public = null,
        public readonly array $currencies = [],
        public readonly string $regions = '',
        public readonly Filter|string|null $filter = null,
        public readonly Kind|string|null $kind = null,
        public readonly ?bool $following = null,
        public readonly ?DateTimeImmutable $lastPull = null,
        public readonly PanicPeriod|string|null $panicPeriod = null,
        public readonly PanicSort|string|null $panicSort = null,
        public readonly ?int $size = null,
        public readonly ?int $page = null,
        public readonly ?bool $withContent = null,
        public readonly string $search = '',
    ) {
    }

    /**
     * Create a new instance with modified values (immutable builder).
     *
     * @param array<string, mixed> $overrides
     */
    public function with(array $overrides): self
    {
        $reflection = new \ReflectionClass(self::class);
        $args = [];
        foreach ($reflection->getConstructor()->getParameters() as $param) {
            $name = $param->getName();
            $args[$name] = array_key_exists($name, $overrides) ? $overrides[$name] : $this->{$name};
        }
        return new self(...$args);
    }

    /**
     * Validate local client-side syntax and cross-field rules.
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        $panicSortValue = $this->panicSort instanceof PanicSort ? $this->panicSort->value : $this->panicSort;
        $panicPeriodValue = $this->panicPeriod instanceof PanicPeriod ? $this->panicPeriod->value : $this->panicPeriod;

        if ($panicSortValue !== null && $panicSortValue !== '' && ($panicPeriodValue === null || $panicPeriodValue === '')) {
            throw new ValidationException('panic_sort requires panic_period to be set.');
        }

        if ($this->size !== null && ($this->size < 1 || $this->size > 50)) {
            throw new ValidationException('size must be between 1 and 50 (inclusive).');
        }

        if ($this->page !== null && ($this->page < 1 || $this->page > 50)) {
            throw new ValidationException('page must be between 1 and 50 (inclusive).');
        }

        if ($this->search !== '' && trim($this->search) === '') {
            throw new ValidationException('search must be a non-empty string.');
        }
    }

    /**
     * Convert the query to an array of URL parameters (without auth_token).
     *
     * @return array<string, mixed>
     */
    public function toQueryArray(): array
    {
        $params = [];

        if ($this->public !== null) {
            $params['public'] = $this->public ? 'true' : 'false';
        }

        if (!empty($this->currencies)) {
            $params['currencies'] = implode(',', $this->currencies);
        }

        if ($this->regions !== '') {
            $params['regions'] = $this->regions;
        }

        $filterValue = $this->filter instanceof Filter ? $this->filter->value : $this->filter;
        if ($filterValue !== null && $filterValue !== '') {
            $params['filter'] = $filterValue;
        }

        $kindValue = $this->kind instanceof Kind ? $this->kind->value : $this->kind;
        if ($kindValue !== null && $kindValue !== '') {
            $params['kind'] = $kindValue;
        }

        if ($this->following !== null) {
            $params['following'] = $this->following ? 'true' : 'false';
        }

        if ($this->lastPull !== null) {
            $params['last_pull'] = $this->lastPull->format(DateTimeImmutable::ATOM);
        }

        $panicPeriodValue = $this->panicPeriod instanceof PanicPeriod ? $this->panicPeriod->value : $this->panicPeriod;
        if ($panicPeriodValue !== null && $panicPeriodValue !== '') {
            $params['panic_period'] = $panicPeriodValue;
        }

        $panicSortValue = $this->panicSort instanceof PanicSort ? $this->panicSort->value : $this->panicSort;
        if ($panicSortValue !== null && $panicSortValue !== '') {
            $params['panic_sort'] = $panicSortValue;
        }

        if ($this->size !== null) {
            $params['size'] = (string)$this->size;
        }

        if ($this->page !== null) {
            $params['page'] = (string)$this->page;
        }

        if ($this->withContent !== null) {
            $params['with_content'] = $this->withContent ? 'true' : 'false';
        }

        if ($this->search !== '') {
            $params['search'] = $this->search;
        }

        return $params;
    }
}
