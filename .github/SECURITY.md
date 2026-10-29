# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.x (latest minor) | :white_check_mark: |
| < 2.0   | :x:                |

Security fixes are released as patch versions of the latest 2.x minor. Upgrade to the latest 2.x release before reporting an issue found on an older one.

## Reporting a Vulnerability

We take the security of FormKitBundle (`nowo-tech/form-kit-bundle`) seriously. If you believe you have found a security vulnerability, please report it to us as described below.

### How to Report

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, report it privately:

- **Email**: **hectorfranco@nowo.tech**

Include the following information in your report:

- Type of issue (e.g., injection, XSS, CSRF bypass, auth bypass, deserialization risk, etc.)
- Full paths of source file(s) related to the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

### Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Resolution**: Varies depending on complexity

### Disclosure Policy

- We will confirm receipt of your vulnerability report
- We will work with you to understand and validate the issue
- We will develop and release a fix as quickly as possible
- We will publicly acknowledge your responsible disclosure (if desired)

## Preferred Languages

We prefer all communications to be in English or Spanish.

## Contact

- **Maintainer**: [Héctor Franco Aceituno](https://github.com/HecFranco)
- **Organization**: [nowo-tech](https://github.com/nowo-tech)

The bundle-specific threat model, mitigations and release checklist are in [`docs/SECURITY.md`](../docs/SECURITY.md).
