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
## 2025-05-14 - [In-place Options Optimization]
**Learning:** Modifying the `$options` array directly and avoiding spread-operator merges when defaults are sufficient can save memory and CPU cycles in high-throughput clients.
**Action:** Detect cases where default headers can be used as-is to skip merging logic.
## 2025-05-14 - [Hot Path Header Optimization]
**Learning:** In the `forward()` method, which is the most called method in the bundle, avoiding array spreads and merges for headers when no custom options are provided significantly reduces CPU overhead. Handling the `correlationId` case separately is more efficient.
**Action:** Use conditional assignment for default headers to avoid merging logic whenever possible.
## 2025-05-14 - [Static Request Body Optimization]
**Learning:** For predictable API requests like OAuth2 token exchanges, pre-calculating the request body in the constructor avoids redundant array operations during every refresh attempt.
**Action:** Move static API request bodies to class properties.
## 2025-05-14 - [Memory & Security in Cache Keys]
**Learning:** Initializing cache keys based on all relevant credentials (hashed) ensures perfect isolation between different bundle instances in the same environment. Removing these credentials from object properties after they've been used in the constructor reduces the object's memory footprint and improves security by not keeping sensitive data in memory longer than necessary.
**Action:** Hash all identifying credentials for cache keys and avoid keeping them as class properties if only needed for initialization.
## 2025-05-14 - [PHP Engine Micro-optimizations]
**Learning:** Adding leading backslashes to global function calls (e.g., `\time()`) avoids the engine checking the current namespace first, which can save time in tight loops. Using the `+` operator for array merging is often faster than `array_merge` or the spread operator when keys are known not to be duplicate or when simple override logic is sufficient.
**Action:** Use leading backslashes for all global PHP functions and prefer the `+` operator for efficient array merging where appropriate.
## 2025-05-14 - [Public Readonly for DTOs]
**Learning:** For high-access DTOs, using `public readonly` properties instead of private properties with getters can slightly reduce method call overhead while maintaining immutability.
**Action:** Prefer `public readonly` for DTO data fields when targeting PHP 8.2+.
## 2025-05-14 - [Static Serialized Body]
**Learning:** For OAuth2 token requests, pre-building the serialized request body (`application/x-www-form-urlencoded`) in the constructor avoids the overhead of repeated `http_build_query` calls during token refresh.
**Action:** Pre-serialize static API request bodies in the constructor.
## 2025-05-14 - [Static Cache Closures]
**Learning:** Using `static` closures for cache retrieval prevents the closure from unnecessarily binding `$this`. This can slightly reduce memory overhead and prevents potential memory leaks or unintended state capture in long-lived services.
**Action:** Use `static function` for cache closures whenever the closure doesn't strictly require `$this`.
## 2025-05-14 - [Closure Optimization]
**Learning:** Captured variables in closures should be as minimal as possible. Pre-calculating values (like current time) outside the closure and then using them inside ensures that every invocation of the closure uses the same base data and avoids redundant system calls.
**Action:** Minimize and refine closure captures for hot-path logic.
## 2025-05-14 - [Scoped HttpClients]
**Learning:** Using `withOptions()` to create a scoped HttpClient for default headers is more efficient than manual array merging in every request method. However, this may require updating mocks in unit tests to handle the additional `withOptions()` call.
**Action:** Use scoped clients via `withOptions()` for persistent configuration.
## 2025-05-14 - [Dependency Minimization]
**Learning:** Periodically auditing `composer.json` for unused dependencies (like `symfony/uid` in this bundle) reduces the package footprint, speeds up installation, and minimizes the security attack surface.
**Action:** Remove unused dependencies from `composer.json`.
## 2025-05-14 - [PHP Native Efficiency]
**Learning:** Backslashing global constants (e.g., `\JSON_THROW_ON_ERROR`) and utilizing class constants for repeated strings (like API paths) avoids redundant lookups. Consistently reusing pre-captured timestamps in complex logic (like cache closures) ensures data integrity and reduces system clock calls.
**Action:** Use leading backslashes for all global PHP identifiers and consolidate repeated strings into class constants.
