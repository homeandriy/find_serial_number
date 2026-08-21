# Changelog

## v0.12.0 - 2026-08-21

### Added

- Add a Help menu action to open the startup log and show its result in the application.
- Add a manual update check from Help with a status dialog.

### Changed

- Defer image catalog loading until the first application screen is displayed.

### Fixed

- Reduce NativePHP startup work and make updater checks use published GitHub releases.

## v0.11.0 - 2026-08-21

### Added

- Add a lazy-loaded Statistics tab with bar charts for receipts, issues, services and equipment names.
- Add daily statistics from the first day of the current month and a selectable list of the latest twelve months.
## v0.10.0 - 2026-08-20

### Added

- Show an in-app «Про програму» window from the Help menu with version and developer contact details.
- Add a production startup splash screen while the desktop application loads.
- Add «Переглянути» to each image menu to open the photo in the default Windows image viewer.

### Fixed

- Make image-menu actions independent so viewing, rotating and deleting photos cannot trigger one another.
## v0.9.0 - 2026-08-20

### Added

- Add equipment operations, contract numbers, source-photo links, editing and export support.
- Add a local application launch counter and first-run setup flow.
- Add 208 bundled device models plus ten quick-select popular-model buttons.
- Bundle portable OCR configuration and production-friendly Windows icon handling.

### Changed

- Use Kyiv time consistently for equipment records and show popular models by actual entry count.
- Improve image-folder configuration, first-run setup and Windows folder dialogs.

### Fixed

- Preserve forward-only SQLite migrations during GitHub updater installs.
- Correct production OCR runtime discovery and application menu/icon behaviour.
## v0.8.3 - 2026-08-20

### Added

- Publish the first GitHub updater-compatible production release with installer metadata and block map.
## v0.8.2 - 2026-08-20

### Added

- Check public GitHub Releases at production startup, download updates, install them and relaunch the application.
- Run forward-only Laravel migrations on production startup without resetting local SQLite data.

### Changed

- Use the stable NativePHP application identifier ua.homeandriy.serialvision.
## v0.8.1 - 2026-08-20

### Changed

- Document portable OCR in the installer and development setup.
## v0.7.3 - 2026-08-20

### Changed

- Use the Serial Vision logo for the Windows application, shortcut, installer, and favicon.
## v0.7.2 - 2026-08-20

### Fixed

- Exclude local images from the Windows package.

### Changed

- Add a native folder-picker button to image settings.


## v0.8.0 - 2026-08-20

### Added

- Bundle portable Tesseract OCR runtime into Windows builds for offline local recognition.

