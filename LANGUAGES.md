## Generating .mo files for translations

This repository contains translation source files in `languages/*.po`.
To make WordPress load translations, compile each `.po` into a binary `.mo` file.

### Workflow:

Developer: Adds new code with translatable text.
Developer: Runs generate pot file in vscode for translations.
Translator: Edits the .po files to add new translations, or use Google translate in https://es.wordpress.org/plugins/loco-translate/
Developer/Translator: Runs Compile translation (vscode task) .po -> .mo to make the new translations live on the site.


In Vscode, run the Build task to recompile all .mo files after changes.

## Updating .po files from source code

To extract translatable strings from the plugin source and update `.po` files:

### Using [wp-cli](https://wp-cli.org/#installing) (recommended)
```bash
wp i18n make-pot . languages/tumult-hype-animations.pot --domain=tumult-hype-animations
wp i18n update-po languages/tumult-hype-animations.pot languages/
```

### Using Poedit
1. Open existing `.po` files in Poedit
2. Go to **Catalog > Update from POT file**
3. Select `languages/tumult-hype-animations.pot`
4. Review new/changed strings and translate
5. Save to generate `.mo` automatically
