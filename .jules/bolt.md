## 2025-05-14 - [Memoization & Merge Optimization]
**Learning:** Class-level `readonly` prevents in-memory memoization. Switching to property-level `readonly` (PHP 8.2) allows adding private non-readonly properties for caching without sacrificing external immutability. Also, `array_merge_recursive` for HttpClient options can be dangerous and slow.
**Action:** Use property-level `readonly` for DTOs/Services that need internal state for performance. Prefer spread operator or manual merging for HttpClient options to ensure correct header overrides.
## 2025-05-14 - [In-memory caching and TTL]
**Learning:** When implementing in-memory caching for tokens, it is critical to also track and respect the expiration time (TTL). Relying solely on the presence of a token in a property can lead to using expired tokens if the object persists beyond the token's lifetime.
**Action:** Always store the expiration timestamp alongside the cached value and check it before returning the memoized result.
## 2025-05-14 - [Static Header Optimization]
**Learning:** Pre-calculating static headers in the constructor of a client can reduce array creation and conditional logic overhead in high-frequency request methods.
**Action:** Move static request configuration to class properties during initialization.

## 2025-05-14 - [Credential-Specific Cache Keys]
**Learning:** Shared cache prefixes can lead to token collisions if multiple sets of credentials are used in the same environment.
**Action:** Include a hash of relevant credentials in the cache key to ensure isolation and prevent redundant re-authentications.
## 2025-05-14 - [Streaming Proxy Optimization]
**Learning:** For proxy controllers, using `getContent(true)` to pass the request body as a resource allows the HttpClient to stream the data instead of buffering it into memory. Skipping body processing for `GET`/`HEAD` requests further reduces overhead.
**Action:** Use resource streaming for request forwarding and skip body buffering for read-only methods.
## 2025-05-14 - [Proxy Body & Retry Fix]
**Learning:** When using `getContent(true)` in a proxy controller, the body is a resource that can only be read once. If the HttpClient needs to retry (e.g., after a 401 token refresh), the second request will have an empty body.
**Action:** Use a callable for the `body` option (`fn() => $request->getContent(true)`) to allow the HttpClient to re-open the resource for retries.

## 2025-05-14 - [Micro-optimizations in PHP]
**Learning:** `isset` with a constant array (acting as a set) is faster than `in_array`. `array_diff_key` with a constant array is more efficient for filtering multiple keys than multiple `unset` calls.
**Action:** Prefer `isset` for set membership checks and `array_diff_key` for bulk key removal.
## 2025-05-14 - [Cache Reliability in Long-lived Processes]
**Learning:** In long-running PHP processes (like worker queues), simple in-memory properties for tokens can become out of sync with the primary cache if not handled carefully. Storing both the token and its precise expiration timestamp in the cache ensures that any process fetching from the cache can accurately reconstruct the valid in-memory state.
**Action:** Store `['token' => ..., 'expires_at' => ...]` in the cache and use it to sync internal object state.
