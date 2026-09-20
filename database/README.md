# Database datasets

| Demo | File | Former base URL |
|---|---|---|
| Nail | `beautia_nail.sql` | `http://localhost/beautia/demoes/nail` |
| Clinic | `beautia_clinic.sql` | `http://localhost/beautia/demoes/clinic` |
| Hair | `beautia_hair.sql` | `http://localhost/beautia/demoes/hair` |
| Spa | `beautia_spa.sql` | `http://localhost/beautia/demoes/spa` |
| Lashes | `beautia_lashes.sql` | `http://localhost/beautia/demoes/lashes` |
| Makeup | `beautia_makeup.sql` | `http://localhost/beautia/demoes/makeup` |
| Barber | `beautia_barber.sql` | `http://localhost/beautia/demoes/barber` |

## Import order

1. Create an empty database using UTF-8/utf8mb4.
2. Install WordPress and the source from `core/`.
3. Import one and only one SQL file.
4. Run a serialization-safe search/replace for the former URL to the target URL.
5. Update `home` and `siteurl`; save permalinks; create a fresh administrator.

These dumps were sanitized for Git: development user rows, OTP transient values, and SMS logs were removed. They are content/configuration datasets, not ready-to-run production databases. Media packages for six demos are not available in this migration.
