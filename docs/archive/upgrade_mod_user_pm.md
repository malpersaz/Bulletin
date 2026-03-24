# Modernization Plan for mod_user_pm Inbox

## Objective
Update the `templates/admin/mod_user_pm/inbox.twig` to match the modern, mobile-first design language used in `templates/admin/mod_content/list.twig`. This includes adopting the new layout structure, stats cards, and dynamic content loading patterns.

## Proposed Changes

### 1. Layout & Configuration
- Set `hide_navbar = true`, `hide_fab = true`, and `show_search = true`.
- Extend `admin/layout.twig` instead of `management/admin-layout.twig`.
- Define `block title` and `block headline`.

### 2. Header & Stats
- Implement a "Quick Stats & Actions" section similar to the content list.
- Add stat cards for "Total Messages" and "Unread Messages".
- Maintain the navigation links (Inbox, Sent Box) but styled to fit the new design (perhaps as a toggle or integrated into the header/toolbar).

### 3. Content Structure
- Replace the static loop with a dynamic container (`#message-list-container`).
- Implement the "Pull to Refresh" spinner.
- Add an "Empty State" section for when no messages are found.
- Add an "Infinite Scroll Loader" for paginated content.

### 4. Interactive Toolbar
- Implement the `block fixed_bottom` with a `standard-toolbar`.
- Toolbar items: "New Message" (replacing the floating action), "Read", "Reply", and "Delete".

### 5. JavaScript Integration
- Create or update the necessary JS to handle data fetching from the PM module's JSON endpoint.
- Ensure the same smooth transitions and mobile-optimized interactions as the content module.

## UI/UX Improvements
- Use the material-style card design for message previews.
- Highlight unread messages with a distinct left border or background color.
- Optimize for touch targets and mobile responsiveness.
