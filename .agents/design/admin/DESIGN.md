# Admin Design Contract

## Purpose

Define baseline design expectations for `templates/admin` work.

The admin panel is a phone-only admin surface. This document reinforces `templates/admin/AGENTS.md`; it does not replace it.

## Required Context

Before using this file:

1. Read root `AGENTS.md`.
2. Read `templates/AGENTS.md`.
3. Read `templates/admin/AGENTS.md`.
4. Read only the admin profile needed for the current task.

## Phone-Only Principles

- Design for a fixed phone-sized app shell.
- Prioritize compact vertical flow, one-hand reach, and touch interaction.
- Keep the primary task and primary action visible early.
- Avoid decorative chrome that pushes work below the early scroll.
- Do not spend effort on larger-screen admin polish unless the user explicitly requests it.

## Touch And Density

- Controls must be easy to tap without accidental neighboring taps.
- Icon-only actions need recognizable icons or existing local patterns.
- Dense lists are acceptable when labels, state, and actions remain clear.
- Do not let text overflow button, card, or toolbar bounds.

## Form Flow

- Add/edit forms should show the next required decision clearly.
- Validation feedback should appear near the affected field when possible.
- Persistent save bars or bottom actions must not hide form fields.
- Language, parent, status, and media controls must stay reachable by touch.

## List Workspace

- Lists should support scanning, filtering, status recognition, and direct edit paths.
- Card rows should keep title, metadata, state, and primary action in stable positions.
- Progressive loading or filtering must preserve orientation and avoid sudden layout jumps.

## States

- Empty, loading, success, and error states must use real localization keys.
- Visible copy must not use inline fallback behavior.
- State visuals should clarify the next action without adding decorative bulk.

## Boundaries

- Do not use public theme profiles for admin screens.
- Do not copy legacy admin markup without modernizing the touched flow.
- Keep admin assets under `templates/admin/assets/`.
- Validate only phone-sized admin behavior unless explicitly overridden.
