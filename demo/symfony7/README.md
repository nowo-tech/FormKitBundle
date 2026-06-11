# Form Kit Bundle Demo — Symfony 7.4

Requires **PHP 8.2+** (aligned with bundle requirements and Symfony 7.4).

Run with Docker (from this directory):

```bash
make up
```

Then open http://localhost:8007 (or the port in `PORT`).

The demo shows a simple contact form whose labels, placeholders and help texts are resolved by convention: `demo_contact.full_name.label`, `demo_contact.email_address.placeholder`, etc., as configured by Form Kit Bundle.
