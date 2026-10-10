# PivotPHP v4.1.0 - Framework Overview

**Version:** 4.1.0 (Minor)
**Release Date:** 2026-10-10
**PHP Requirements:** 8.1+

## Summary

Minor release on the 4.x line, focused on the `Validator` semantics and OpenAPI generation.

### Added

- **`nullable` and `sometimes` rules in `Validator`** (SPEC-069): fields absent that do not carry the
  `required` rule no longer fail by default, and fields with `nullable` accept `null`.
- **Type-aware `min`/`max` and array item counting** (SPEC-069): numeric values are compared by value
  (including decimals such as `9.99`), strings are measured in UTF-8 characters (`mb_strlen`), and arrays
  have their element count validated.
- **Strict `in` matching and `regex` string validation** (SPEC-069).
- **Unknown-rule validation** (SPEC-069): an unknown or mistyped rule now throws `\InvalidArgumentException`.
- **OpenAPI 3.0 path templating and parameters** (SPEC-056): `ApiDocumentationMiddleware` normalizes route
  parameters (`:param`, `:param<regex>`, `{param<regex>}`) to `{param}` and generates the matching
  `parameters` objects (`in: path`, `required: true`).

## Compatibility

- Backwards compatible with 4.0.x. No breaking changes.

See the [CHANGELOG](../../CHANGELOG.md) for the full list and
[FRAMEWORK_OVERVIEW_v4.0.0.md](FRAMEWORK_OVERVIEW_v4.0.0.md) for the 4.0 ecosystem overview.
