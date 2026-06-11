# Form Kit Bundle Demo — Symfony 8.1

Requires **PHP 8.4+** (Symfony 8 platform requirement). The demo pins Symfony **8.1.\*** components.

Run with Docker (from this directory):

```bash
make up
```

Then open http://localhost:8008 (or the port in `PORT`).

The demo shows a simple contact form whose labels, placeholders and help texts are resolved by convention: `demo_contact.full_name.label`, `demo_contact.email_address.placeholder`, etc., as configured by Form Kit Bundle.
