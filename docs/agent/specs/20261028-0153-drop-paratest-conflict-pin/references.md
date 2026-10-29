# References

## Keystone PR #8

- Location: `../Keystone` repository, pull request #8 (Refactor-Circus/Keystone).
- Relevance: the same pin was dropped there.
- Pattern to borrow: remove the `conflict` entry, bump `pestphp/pest` to `^5.3.4`, update the lock.

## composer.json

- Location: `composer.json:32` (pest constraint), `composer.json:176-177` (conflict entry).
- Relevance: the only file whose constraints change.
