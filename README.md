# Event-Blocks

Event-Blocks is a WordPress plugin for managing events and displaying them as styled event cards via shortcode.

## Features

- Custom post type for events
- Event categories with configurable badge colors
- Event cards rendered with shortcode
- Design settings page with live preview
- Configurable frontend labels and texts
- Support for announcement events that display only month and year

## Shortcode

Use the shortcode below to render event cards:

```text
[event_blocks]
```

Optional attributes:

- `limit="3"` to limit the number of events
- `show_past="1"` to include past events

## Admin

The plugin adds an `Event Blocks` menu in WordPress admin.

You can:

- create and edit events
- manage event categories
- customize card styling
- edit visible frontend texts

## Installation

1. Download `event-blocks.zip` from the matching GitHub release.
2. In WordPress admin, go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP file, install it, and activate the plugin.

## Notes

- Events are sorted chronologically by their internal event date
- Announcement events still store an exact date internally, but show only month and year in the frontend

## Reference

The plugin is used live here:

- `https://coldoak.de/veranstaltungen/`
