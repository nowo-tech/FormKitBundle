# Symfony Flex recipe

Form Kit ships a **recipe stub** under [`.symfony/recipe/nowo-tech/form-kit-bundle/2.0/`](../.symfony/recipe/nowo-tech/form-kit-bundle/2.0/) for maintainers and for a future PR to [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib).

Until the recipe is published on recipes-contrib, Flex users must register the bundle and config manually — see [INSTALLATION.md](INSTALLATION.md).

## Recipe contents (2.0)

| File | Purpose |
|------|---------|
| `manifest.json` | Register the bundle; copy `config/` into the project |
| `config/packages/nowo_form_kit.yaml` | Default `configs` / `help_modal` / Bootstrap-oriented `defaults` |
| `config/packages/nowo_form_kit_twig.yaml` | Adds `@NowoFormKit/form/static_blocks.html.twig` to `twig.form_themes` |
| `post-install.txt` | Short install summary printed by Flex |

## Submit to recipes-contrib

1. Fork [symfony/recipes-contrib](https://github.com/symfony/recipes-contrib).
2. Copy the `2.0/` folder to `nowo-tech/form-kit-bundle/2.0/` in that fork (same layout as other vendor recipes).
3. Open a PR following the [recipes-contrib contributing guide](https://github.com/symfony/recipes-contrib/blob/main/CONTRIBUTING.md).
4. After merge, Packagist + Flex will apply the recipe on `composer require nowo-tech/form-kit-bundle`.

Keep the stub in this repository in sync with any upstream recipe changes.

## Local testing (optional)

Point Composer Flex at a local endpoint that serves this tree, or copy the YAML files into a throwaway Symfony app and verify `bin/console debug:config nowo_form_kit` and Twig form themes.
