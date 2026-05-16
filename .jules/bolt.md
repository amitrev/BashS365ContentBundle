## 2025-05-14 - [Memoization & Merge Optimization]
**Learning:** Class-level `readonly` prevents in-memory memoization. Switching to property-level `readonly` (PHP 8.2) allows adding private non-readonly properties for caching without sacrificing external immutability. Also, `array_merge_recursive` for HttpClient options can be dangerous and slow.
**Action:** Use property-level `readonly` for DTOs/Services that need internal state for performance. Prefer spread operator or manual merging for HttpClient options to ensure correct header overrides.
## 2025-05-14 - [In-memory caching and TTL]
**Learning:** When implementing in-memory caching for tokens, it is critical to also track and respect the expiration time (TTL). Relying solely on the presence of a token in a property can lead to using expired tokens if the object persists beyond the token's lifetime.
**Action:** Always store the expiration timestamp alongside the cached value and check it before returning the memoized result.
