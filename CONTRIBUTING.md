Contributing
============

Thank you for contributing to this bundle!

Before we can merge your pull request here are some guidelines that you need to follow.
These guidelines exist not to annoy you, but to keep the code base clean,
unified and future proof.

Tests
--------------

Please try to add a test for your pull request.

You can run the tests by calling:

```bash
composer test
```

Or with docker:

```bash
composer docker -- test
composer docker -- test -- --filter=SomeTest
# optional: remove image when you do not need it anymore
composer docker-clean
```

Code quality
---------------------------

Checking code standard, benchmark, and more.

```bash
composer code-quality
```

Or with docker:

```bash
composer docker -- code-quality
# optional: remove image when you do not need it anymore
composer docker-clean
```

Coding Standard
----------------

You can use to fix coding standard if needed:

```bash
composer fix-cs
```
