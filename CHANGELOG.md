# Changelog

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

