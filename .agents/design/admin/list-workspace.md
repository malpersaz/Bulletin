# Admin List Workspace Profile

## Use When

Use this profile for admin list screens, search/filter surfaces, card rows, sorting, progressive loading, and bulk actions.

## Workspace Goals

- Let editors scan records quickly on a phone.
- Keep search, filters, status, and primary add/edit actions easy to reach.
- Preserve module-specific list behavior and permissions.

## List Structure

- Use stable card or row patterns with predictable title, metadata, state, and action placement.
- Keep images or icons helpful but secondary to record identity.
- Avoid nested controls that trigger the wrong row action.

## Filtering And Loading

- Search and filters should update results without losing orientation.
- Empty filtered states should explain the result and preserve the way back.
- Progressive loading should keep scroll position and avoid layout jumps.

## Bulk And Row Actions

- Bulk actions must be explicit and reversible where the module supports it.
- Destructive row actions need confirmation when existing module patterns require it.
- Keep edit, delete, reorder, status, and media actions visually distinct.

## Validation Checklist

- Phone-sized list can be scanned and operated by touch.
- Search/filter states are localized.
- Empty, loading, success, and error states use real language keys.
- No large-screen-only dependency was introduced.
