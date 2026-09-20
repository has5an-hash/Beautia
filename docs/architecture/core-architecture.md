# Core architecture

`core/beautia-theme-source` is the shared Beautia theme. Important entry points recorded in the supplied handoff are `functions.php`, `front-page.php`, `header.php`, `assets/css/`, `assets/js/`, `inc/`, and `page-templates/`.

`core/beautia-core` is the supplied companion plugin. It contains booking, OTP, REST, SMS, account, custom-post-type, Elementor and demo-import related code. Configuration values must be supplied at runtime and must not be committed.

The repository currently preserves one extracted shared source snapshot; equivalence against each lost demo file package remains unverified.
