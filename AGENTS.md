# Cookie Choices

* Mandatory for the project is to use DDD and TDD
* Use the `Makefile` targets as shortcuts: `make help` lists them, PHP runs in the Docker containers.
* After every change run the checks: `make cs stan integration e2e js-check`, then `make release-build e2e-build`.
* New hooks are PSR-14 event objects, not WordPress filters.
* A settings change needs a Migration step for the stored option.
* Translate every new string in Italian with `make i18n`, write the docs in `docs/` and `docs/it/`.
* Commits end with `Refs: #N`, one pull request per issue, squash-merge.
