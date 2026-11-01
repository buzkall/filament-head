# Screenshots

| File | Shows |
|---|---|
| `section.png` | The `SEO & sharing` section opened, single locale, cropped to the section's frame. |
| `locale-tabs.png` | The same section with two locales configured, showing the tabs. |
| `collapsed.png` | The whole edit form, with the section collapsed as it starts. |

All three were taken in light mode from the `workbench/` demo app, which is a real Filament panel.
Set it up without `composer build`, which wipes the workbench database:

```bash
php vendor/bin/testbench workbench:create-sqlite-db
php vendor/bin/testbench vendor:publish --tag=laravel-assets --force
php vendor/bin/testbench filament:assets
php vendor/bin/testbench migrate
php vendor/bin/testbench db:seed --class='Workbench\Database\Seeders\DatabaseSeeder'
php vendor/bin/testbench serve
```

Then open `/login-as-admin` — it signs in as the seeded admin and lands on the posts
list. Edit the seeded post to reach the form; the seeder leaves the Open Graph fields, content
type and robots blank so their placeholders show. For the tabbed variant, temporarily register
the plugin with `->locales(['en', 'es'])` on `Workbench\App\Providers\AdminPanelProvider`.

This directory is export-ignored, so the images never ship in the Composer dist.
