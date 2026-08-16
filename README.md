# Taqbook

## Code formatting

PHP is formatted by [Pint](https://laravel.com/docs/pint), JavaScript and CSS by
[Prettier](https://prettier.io). Both run automatically:

- **on commit** — staged files are formatted in place and re-staged;
- **on push** — the whole tree is re-checked, and the push is refused if anything is unformatted.

The hooks live in `.githooks/`. `composer install` and `npm install` both point Git at them, so a
fresh clone is set up already. To wire an existing clone by hand:

```
git config core.hooksPath .githooks
```

To format everything yourself:

```
vendor/bin/pint     # PHP
npm run format      # JavaScript and CSS
```

Legacy FrontAccounting sources under `public/` are deliberately left unformatted, as are Blade
templates.

### If the hooks can't find PHP

The hooks need PHP 8.1 or newer, and the `php` first on your `PATH` is often an older build bundled
with XAMPP, Laragon or similar. Pin the right one for your clone:

```
git config taqbook.php /path/to/php
```

On Windows, run the Git commands above from Git Bash.
