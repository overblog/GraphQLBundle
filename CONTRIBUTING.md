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
docker build . -t graphql-test && docker image prune -f >/dev/null && docker run --rm graphql-test test
```

Code quality
---------------------------

Checking code standard, benchmark, and more.

```bash
composer code-quality
```

Or with docker:

```bash
docker build . -t graphql-test && docker image prune -f >/dev/null && docker run --rm graphql-test code-quality
```

Coding Standard
----------------

You can use to fix coding standard if needed:

```bash
composer fix-cs
```
