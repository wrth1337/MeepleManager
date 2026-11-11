# MeepleManager

MeepleManager is a WordPress plugin for club events and a board-game lending inventory. It keeps the existing Event-Blocks event data and `[event_blocks]` shortcode compatible while adding a public game catalog, a lending area, request handling, lending history, and CSV inventory tools.

## Features

- Existing event management, categories, card styling, and frontend labels
- Game inventory with unique inventory numbers, fixed lending categories, condition notes, featured images with adjustable fitting and position, and favorites
- Public game catalog with card and table views, title search, game-category filters, and sorting by title, player count, or play time
- Game cards that flip to show player count, play time, and assigned game categories
- Configurable placeholder image and opacity, image fit and position, card frame thickness, favorite flame colors, and colors for catalog cards and the lending area
- Configurable thickness for lending-table lines
- Configurable border thickness for the lending-request box
- Configurable background, text, and accent colors for request confirmations
- Lending list with availability and planned return dates; page visibility is managed through WordPress
- Searchable lending table sorted by inventory number by default, with sorting by inventory number or title and a compact, labeled mobile layout
- Member requests that staff approve or reject in the WordPress admin
- Edit each loan-approval email before sending it, including handover details
- Approve or reject requests with the matching editable email, or delete a pending request without sending an email
- Automatic request, approval, and rejection emails through WordPress mail
- Admin-only active-loan list and return history
- CSV import and export for game inventory, including player counts, play times, game categories, image references, and publication status
- Automatic anonymization of borrower contact details three months after return

## Shortcodes

The existing event cards remain available with the same shortcode and options:

```text
[event_blocks]
[event_blocks limit="3" show_past="1"]
```

The legacy alias `[vereins_events]` also remains registered.

Add the public game-card catalog to a page:

```text
[game-list]
```

Create a WordPress page and add the following shortcode:

```text
[game-lending]
```

The previous `[meeple-lending]` shortcode remains available as an alias.

Manage access using the page's WordPress visibility settings, for example **Password protected**. MeepleManager does not require a page password, display its own password form, or check password cookies when rendering or submitting requests. The request form retains its page-specific nonce validation. The lending list never displays borrower names or email addresses.

## Admin

Open **MeepleManager** in the WordPress admin to manage games and events. Users with administrator permissions can also:

- Review and approve or reject requests
- Set checkout and planned return dates
- Record the actual return date with **Ist zurück**
- See borrower details and the loan history
- Configure the games warden email and email templates
- Edit the approval email per request before sending, with placeholders for member, game, inventory number, and loan dates
- Adjust the catalog and lending-area colors under **Spiele-Design**
- Choose the fallback image and its opacity, and set both favorite-flame colors under **Spiele-Design**
- Set game-image fitting and focal position under **Spiele-Design**, with optional per-game position overrides in each game editor
- Adjust the lending-request heading color under **Spiele-Design**
- Adjust lending-table line thickness under **Spiele-Design**
- Adjust the lending-request box border thickness under **Spiele-Design**
- Customize the request-confirmation background, text, and accent colors under **Spiele-Design**
- Search lending games by title and sort by inventory number or title in the table header
- Mark games as **Aktueller Favorit** in the game editor; favorites appear first in `[game-list]` with a flame and the hover label **Heiß**
- Enter player-count and play-time ranges in the game editor and create game categories under **Spielkategorien**
- Filter the public catalog by game category; hover over a card, tap it, or use Enter/Space to toggle its details
- Switch between the catalog's card and table views
- Sort the catalog by title, player count, or play time in ascending or descending order
- Import and export the inventory as CSV

To add a game image, set its **Beitragsbild** in the game editor. By default, the full image fits inside the square card. Under **Spiele-Design**, you can instead fill the card and set a global focal position; each game can override its horizontal or vertical position in the editor.

Both catalog views use uncropped source images, with fitting controlled by **Spiele-Design**. Disabling a category hides every game assigned to it, even if another category is still enabled. Disabling a parent category also hides games in its subcategories; games without categories remain visible. Player-count and play-time sorting uses the minimum value, or the maximum when only that value is known. Games with neither value remain at the end in both sort directions.

An active loan makes a game appear as unavailable. Pending requests do not reserve a game. The warden coordinates competing requests outside the plugin.

Games with missing or invalid lending categories are shown as unavailable until their category is saved in the game editor. Decisions for the same game are processed one at a time to prevent simultaneous approvals or conflicting actions on a request.

## CSV format

CSV export uses these headers:

```text
Inventarnummer,Titel,Ausleihkategorie,Zustand,Favorit,Spieleranzahl min,Spieleranzahl max,Spieldauer min,Spieldauer max,Spielkategorien,Bild-URL,Bildposition X,Bildposition Y,Status
```

Only inventory number and title are required. Legacy CSV files remain supported, including the old `Kategorie` header for the green/yellow/red lending category. The importer accepts comma- or semicolon-separated UTF-8 files, with or without a byte-order mark (BOM).

| German header | English alias | Value |
| --- | --- | --- |
| Inventarnummer | `inventory_number` | Unique inventory number; existing numbers are skipped |
| Titel | `title` | Game title |
| Ausleihkategorie | `category`, `lending_category` | Grün/Green, Gelb/Yellow, Rot/Red; defaults to green |
| Zustand | `condition` | Condition notes, including line breaks |
| Favorit | `favorite` | `Ja`, `Yes`, `1`, or `true` enables the favorite flag |
| Spieleranzahl min / max | `players_min`, `players_max` | Positive whole numbers; either bound can be left empty |
| Spieldauer min / max | `duration_min`, `duration_max` | Positive whole numbers in minutes; either bound can be left empty |
| Spielkategorien | `game_categories` | JSON list of category paths, or names separated by `\|` |
| Bild-URL | `image_url` | Featured-image URL; matched against the destination site's media library |
| Bildposition X / Y | `image_position_x`, `image_position_y` | Whole numbers from 0 to 100; empty uses the global design default |
| Status | `status`, `post_status` | `publish`, `draft`, `pending`, or `private`; defaults to `publish` |

The exporter writes assigned game categories as JSON paths, for example `[["Brettspiele","Strategie"],["Kooperativ"]]`. This preserves parent categories and names containing commas, quotes, or `|`. Each path assigns its last category; ancestors are created or reused without being automatically assigned to the game. For a manually prepared CSV, `Kooperativ | Strategie` or `["Kooperativ","Strategie"]` creates or reuses top-level categories. Keep the exported JSON value when editing in a spreadsheet; normal CSV quoting is handled by the spreadsheet application.

Invalid numbers, reversed ranges, malformed category lists, and unknown publication statuses cause the affected record to be skipped with an error message. Empty optional values stay empty. Duplicate inventory numbers are skipped without modifying the existing games.

Images are **references, not embedded files**. On import, matching image attachments are reused; files are not downloaded. A missing image is reported, while the rest of the game is imported. For another WordPress installation, transfer the media separately and adapt the image URLs before importing, or assign featured images afterwards.

The CSV includes published, draft, pending, and private games. It is an inventory transfer, not a complete site backup: loan history, personal data, global design settings, media files, and category descriptions/custom slugs are not included.

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
