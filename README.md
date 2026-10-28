# MeepleManager

MeepleManager is a WordPress plugin for club events and a board-game lending inventory. It keeps the existing Event-Blocks event data and `[event_blocks]` shortcode compatible while adding a public game catalog, a password-protected lending area, request handling, lending history, and CSV inventory tools.

## Features

- Existing event management, categories, card styling, and frontend labels
- Game inventory with unique inventory numbers, fixed lending categories, and condition notes
- Public title-only game catalog
- Password-protected lending list with availability and planned return dates
- Member requests that staff approve or reject in the WordPress admin
- Automatic request, approval, and rejection emails through WordPress mail
- Admin-only active-loan list and return history
- CSV import and export for game inventory
- Automatic anonymization of borrower contact details three months after return

## Shortcodes

The existing event cards remain available with the same shortcode and options:

```text
[event_blocks]
[event_blocks limit="3" show_past="1"]
```

The legacy alias `[vereins_events]` also remains registered.

Add the public title list to a page:

```text
[game-list]
```

Add a separate link button to the protected lending page. Set its target under `MeepleManager > Einstellungen`, or pass a URL directly:

```text
[meeple-lending-button]
[meeple-lending-button url="https://example.com/spieleverleih/"]
```

Create a WordPress page, set its visibility to **Password protected**, and add the following shortcode to that page:

```text
[meeple-lending]
```

The shared page password is limited to 20 characters by WordPress. WordPress core does not lock out repeated incorrect attempts for this page password. Use a long, unique password and do not reuse an administrator password. The lending list never displays borrower names or email addresses.

## Admin

Open **MeepleManager** in the WordPress admin to manage games and events. Users with administrator permissions can also:

- Review and approve or reject requests
- Set checkout and planned return dates
- Record the actual return date with **Ist zurück**
- See borrower details and the loan history
- Configure the games warden email and email templates
- Import and export the inventory as CSV

An active loan makes a game appear as unavailable. Pending requests do not reserve a game. The warden coordinates competing requests outside the plugin.

## CSV format

CSV export uses these headers:

```text
Inventarnummer,Titel,Kategorie,Zustand
```

The importer also accepts English column names (`inventory_number`, `title`, `category`, `condition`). Existing inventory numbers are skipped. Missing categories default to green. Loan history and personal data are not part of the inventory CSV.

## Email and scheduled anonymization

WordPress sends email through `wp_mail()`. Delivery depends on the site's mail setup; configure an SMTP provider if the host's default mail delivery is unreliable.

After a loan is returned, borrower first name, last name, and email are automatically removed after three months. WordPress scheduled tasks may run later than their due time if the site receives little traffic.

## Installation

1. Download `meeple-manager.zip` from the matching GitHub release.
2. In WordPress admin, go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP file, install it, and activate MeepleManager.

Event post type identifiers, stored event metadata, style settings, and the `[event_blocks]` shortcode retain their existing names so current event content and embeds continue to work.

### Updating from Event-Blocks

Because the plugin folder and main file are now named `meeple-manager`, WordPress lists MeepleManager as a separate plugin during the changeover. Deactivate Event-Blocks first, then install and activate MeepleManager. Do not leave both active: they share the event code and identifiers. Deactivating the old plugin does not delete event records or style settings; MeepleManager continues to use them.

## Reference

The event feature is used live here:

- `https://coldoak.de/veranstaltungen/`
