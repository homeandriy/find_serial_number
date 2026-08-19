# Changelog

## v0.6.1 - 2026-08-20

### Added

- Paginate equipment and model lists by 100 rows only when a filtered result exceeds 5,000 records.

### Changed

- Export all rows matching the active filters, independent of the displayed page.


## v0.6.0 - 2026-08-20

### Added

- Configure the image folder from the application settings.

### Changed

- Start the desktop window at 1920×1080 and refine refresh/export alignment.


## v0.5.4 - 2026-08-20

### Fixed

- Verify that a rotated image is physically written to disk before reporting success.


## v0.5.3 - 2026-08-20

### Fixed

- Respect the hidden state of OCR and image context menus.


## v0.5.2 - 2026-08-20

### Fixed

- Close both context menus via their Close actions and clicks outside them in NativePHP.


## v0.5.1 - 2026-08-20

### Fixed

- Use unified icon-based context menus with reliable close behavior.
- Refresh the image preview immediately after rotation.


## v0.5.0 - 2026-08-20

### Added

- Group image cards by upload day, show full names, and provide rotate/delete menus.
- Open the homeandriy website in the system browser.
- Replace unlimited AI-agent forms with a table and one CRUD dialog.
- Add an explicit close action and click-away closing for OCR context menus.


## v0.4.6 - 2026-08-20

### Added

- Add normalized and unformatted modes for moving selected OCR text into equipment.
- Add icons to application tabs.

### Changed

- Trim saved equipment text and remove all line breaks.


## v0.4.5 - 2026-08-20

### Fixed

- Filter equipment by complete Kyiv calendar days, including records up to 23:59:59.
- Prevent an end date earlier than the start date and add labeled, individually clearable filters.

## v0.4.4 - 2026-08-20

### Fixed

- Export equipment CSV with Windows-1251 encoding and a semicolon delimiter for Microsoft Excel.

## v0.4.1 - 2026-08-20

### Fixed

- Download Excel export without navigating away from the app.

## v0.4.0 - 2026-08-20

### Added

- Excel export for filtered equipment records and Kyiv-localized date/time display.

## v0.3.3 - 2026-08-20

### Fixed

- Render equipment timestamps in d.m.Y H:i:ss instead of ISO format.

## v0.3.2 - 2026-08-20

### Fixed

- Show device date and time in a manager-friendly format in the equipment table.

## v0.3.1 - 2026-08-20

### Changed

- Store and display device registration date and time; highlight edit and delete actions.

## v0.3.0 - 2026-08-20

### Added

- Full CRUD for device models and saved equipment with filters, editing, and confirmed deletion.

## v0.2.0 - 2026-08-19

### Added

- Local devices database, model directory, and saving selected OCR text.

## v0.1.0 - 2026-08-19

### Added

- Local and AI-assisted equipment-label OCR with configurable encrypted AI agents.
- Copy context menu for selected OCR text and an in-app version indicator.
