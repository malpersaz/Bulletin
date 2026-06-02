# Admin Form Flow Profile

## Use When

Use this profile for admin add/edit forms, wizard steps, validation, media fields, and save flows.

## Flow Goals

- Present one clear next decision at a time.
- Keep required fields and primary save/continue actions easy to reach.
- Preserve language, parent, category, media, and status behavior from the owning module.

## Field Layout

- Group fields by editing task, not database order.
- Keep labels close to inputs and avoid helper text that repeats the label.
- Long controls should wrap cleanly inside the phone shell.
- Media controls must expose change, remove, and preview states without nested tap conflicts.

## Validation

- Show validation near the field or step that needs attention.
- Keep error and success copy localized through the owning module language files.
- Do not mask missing localization with inline visible text.

## Save Behavior

- Save bars should remain reachable without hiding active inputs.
- Disable or guard duplicate submits when the existing flow supports it.
- After save, preserve the module's expected redirect or success behavior.

## Validation Checklist

- Phone-sized form can be completed by touch.
- Required fields are discoverable.
- Primary action remains clear.
- No old and new validators, templates, or aliases remain together unless explicitly requested.
