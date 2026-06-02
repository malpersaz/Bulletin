# Admin Phone Shell Profile

## Use When

Use this profile for `templates/admin` layout shell, top bar, drawer, bottom actions, and persistent app controls.

## Shell Goals

- Keep the current task visible inside a phone-sized app shell.
- Make global navigation reachable without covering important state permanently.
- Keep primary actions near the lower thumb zone when the local pattern supports it.

## Top Bar

- Keep title, context, and essential utility actions compact.
- Avoid long labels that collide with icons or status elements.
- Prefer existing admin icon and button patterns.

## Navigation Drawer

- Drawer content should expose module groups clearly.
- Current module state should remain recognizable after the drawer closes.
- Avoid adding explanatory copy where structure and labels already communicate the path.

## Bottom Actions

- Use bottom actions for primary task commands, not secondary navigation clutter.
- Ensure bottom bars do not cover form fields, list actions, or snackbars.
- Keep destructive actions separated from the primary save or continue action.

## Validation

- Check only phone-sized admin behavior.
- Confirm content remains reachable with the shell open and closed.
- Confirm touch targets, title wrapping, and action placement do not shift the layout unexpectedly.
