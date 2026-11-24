# Cookie Choices

* Mandatory for the project is to use DDD and TDD
* Use the `Makefile` targets as shortcuts: `make help` lists them, PHP runs in the Docker containers.
* After every change run the checks: `make cs stan integration e2e js-check`, then `make release-build e2e-build`.
* New hooks are PSR-14 event objects, not WordPress filters.
* A name, string or value used in more than one place is a constant, or a shared function, never a copy: the setting names are in the `*Setting` class of their context, like `ConsentSetting`, the vendor ids in `VendorId`, the REST namespace in `RestApi`, the routes registered with `RestRoutes`, the WordPress hooks and capabilities used in more classes in `WordPress\Hook` and `WordPress\Capability`. The tests keep the stored keys literal, they check the saved option.
* New front end features are blocks. Add a shortcode only when the output goes inside the page content, for the classic editor and the page builders, as a thin wrapper of the renderer the block uses.
* A settings change needs a Migration step for the stored option.
* Translate every new string in Italian with `make i18n`, write the docs in `docs/` and `docs/it/`.
* Commits end with `Refs: #N`, one pull request per issue, squash-merge.
