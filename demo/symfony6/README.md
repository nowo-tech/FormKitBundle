# Form Kit Bundle Demo — Symfony 6 (legacy)

> **Legacy demo.** Targets **form-kit-bundle 1.x** (PHP 8.1+, Symfony 6.4). Current bundle releases require PHP 8.2+ and Symfony 7.4 / 8.x. Use **demo/symfony7** or **demo/symfony8** for supported stacks.

Run with Docker (from this directory):

```bash
make up
```

Then open http://localhost:8006 (or the port in `PORT`).

The demo shows a simple contact form whose labels, placeholders and help texts are resolved by convention: `demo_contact.full_name.label`, `demo_contact.email_address.placeholder`, etc., as configured by Form Kit Bundle.

Symfony 6 stack: this demo keeps A2lix Translation Form v3 with AutoForm and a minimal Doctrine ORM setup (no app entities) so the same bundle features work as on Symfony 7/8. Application code (controller, forms, Twig) is aligned with the Symfony 8 demo; copy `DATABASE_URL` from `.env.example` into `.env` if it is missing.
