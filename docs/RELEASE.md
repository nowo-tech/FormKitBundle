# Release process

1. Update [CHANGELOG.md](CHANGELOG.md): move entries from `[Unreleased]` to a new `[X.Y.Z] - YYYY-MM-DD` section and add the version link at the bottom. (This project does not store version in `composer.json`; Packagist uses the git tag.)
2. Commit, tag (e.g. `v1.0.0`), and push. The release workflow will create the GitHub Release with the changelog.
3. Publish the package to Packagist if applicable.
