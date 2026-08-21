# Screenshots

| File | Shows |
|---|---|
| `section.png` | The `SEO & sharing` section in a resource form, single locale. |
| `locale-tabs.png` | The same section with two locales configured, showing the tabs. |

Both were taken from the `workbench/` demo app, which is a real Filament panel:

```bash
composer serve            # builds assets, migrates, serves on :8000
php vendor/bin/testbench db:seed --class='Workbench\Database\Seeders\DatabaseSeeder'
```

Then open `/login-as-admin` — it signs in as the seeded admin and lands on the posts
list. Edit the seeded post to reach the form. For the tabbed variant, register the
plugin with `->locales(['en', 'es'])` on `Workbench\App\Providers\AdminPanelProvider`.

This directory is export-ignored, so the images never ship in the Composer dist.
