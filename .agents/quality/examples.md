# Quality Agent Examples

## Purpose

Provide concrete examples of what repository quality agents should report and what they should ignore.

This file is intended to reduce ambiguity and false positives.

## Core Reminder

The repository uses mixed scope enforcement:

- untouched files stay protected
- touched files are file-wide enforced only for:
  - non-English comments
  - non-English hard-coded user-facing strings
- all other categories remain diff-scoped unless explicitly expanded

## Example Group 1: Untouched File

### Case

A legacy file contains:

- Turkish comments
- Kurdish comments
- Turkish visible strings
- old non-English variable names

The current task does not touch that file.

### Expected Result

Agents must stay silent.

### Correct

- `No findings in untouched legacy file.`

### Incorrect

- `This old file still contains Turkish comments and should be cleaned.`

## Example Group 2: Touched File With Old Turkish Comment

### Case

A file is modified for a small bug fix.

That same file already contains:

```js
// resmi burada yeniliyoruz
```

outside the edited hunk.

### Expected Result

This must be reported.

Reason:

- the file is touched
- non-English comments are file-wide enforced in touched files

### Correct

- `Touched file contains a Turkish comment that must be rewritten in English or removed.`

### Incorrect

- `Ignore because this comment is old and outside the diff.`

## Example Group 3: Touched File With Old Turkish UI String

### Case

A touched file contains:

```twig
<button>Kaydet ve devam et</button>
```

and that string remains hard-coded.

### Expected Result

This must be reported.

Reason:

- the file is touched
- non-English hard-coded user-facing strings are file-wide enforced in touched files

### Correct

- `Touched file contains a Turkish hard-coded visible string that must be converted to English and localized appropriately.`

### Incorrect

- `Ignore because the string existed before this change.`

## Example Group 4: Touched File With Old Non-English Variable Name

### Case

A touched file still contains:

```php
$resimYolu = '';
```

but that line is not modified in the current task.

### Expected Result

Do not report by default.

Reason:

- variable naming is still diff-scoped
- the radical file-wide rule does not automatically cover variable names

### Correct

- `No naming finding unless this identifier was introduced or changed in the current diff.`

### Incorrect

- `Touched file has old Turkish variables, so all of them must be renamed now.`

## Example Group 5: New Non-English Variable Name

### Case

The current diff introduces:

```js
const secilenResim = file;
```

### Expected Result

This must be reported.

Reason:

- naming remains diff-scoped
- this identifier is newly introduced in the diff

### Correct

- `New non-English variable name introduced in the diff should be renamed to an English identifier.`

### Incorrect

- `Allowed because the repository already has old Turkish names elsewhere.`

## Example Group 6: New English Hard-Coded String

### Case

The current diff introduces:

```js
showSnackbar('Upload completed');
```

### Expected Result

This should still be reviewed by `i18n-guard` or `template-guard`.

Reason:

- even though the string is English, it may still be an unlocalized hard-coded user-facing string

### Correct

- `New hard-coded user-facing string introduced in the diff should use repository localization structures.`

### Incorrect

- `Allowed automatically because it is English.`

## Example Group 7: Machine String

### Case

The current diff introduces:

```js
return { status: 'success' };
```

### Expected Result

Do not report as a hard-coded string violation.

Reason:

- this is a machine value, not user-facing copy

### Correct

- `No finding.`

### Incorrect

- `The string "success" must be localized.`

## Example Group 8: New Turkish Comment In Diff

### Case

The current diff introduces:

```php
// burada thumbnail yeniden oluşturuluyor
```

### Expected Result

This must be reported as an error.

### Correct

- `New Turkish comment introduced in the diff should be rewritten in English or removed.`

### Incorrect

- `Allowed because comments are internal.`

## Example Group 9: New English Comment

### Case

The current diff introduces:

```js
// Keep the existing preview visible until a new upload succeeds.
```

### Expected Result

Usually allowed if the comment is useful and concise.

### Correct

- `No finding.`

### Incorrect

- `All comments must be removed.`

## Example Group 10: Unused New Language Key

### Case

The current diff adds:

```php
'image_upload_complete' => 'Image upload complete',
```

but the key is never referenced.

### Expected Result

Report as warning.

### Correct

- `New language key added by this change appears unused.`

### Incorrect

- `Ignore because unused keys are never important.`

## Example Group 11: Old Unused Language Key In Untouched File

### Case

A language file not touched by the task already contains unused keys.

### Expected Result

Do not report.

### Correct

- `No finding.`

### Incorrect

- `Repository has many old unused translation keys that must be fixed now.`

## Example Group 12: Touched File With Mixed Situation

### Case

A touched file contains:

- one old Turkish comment
- one old Turkish hard-coded button label
- one old Turkish variable name
- one new English hard-coded snackbar message

### Expected Result

Report:

- old Turkish comment
- old Turkish hard-coded button label
- new English hard-coded snackbar message if it bypasses localization

Do not report by default:

- old Turkish variable name unless changed in the diff

### Correct

- `Touched file still contains a Turkish comment.`
- `Touched file still contains a Turkish hard-coded visible string.`
- `New user-facing English string bypasses localization.`

### Incorrect

- `Touched file must be fully renamed and fully modernized because it was edited once.`

## Example Group 13: Compatibility Alias

### Case

The current diff introduces a compatibility method:

```php
public function remove_image()
{
    return $this->image_remove_image();
}
```

### Expected Result

Usually allowed if needed for backward compatibility, possibly warning-only if undocumented.

### Correct

- `Compatibility alias is acceptable if intentional and documented.`

### Incorrect

- `All compatibility aliases are forbidden.`

## Example Group 14: Non-English Filename In New File

### Case

A new file is created:

- `templates/admin/parcalar/resim_yukleyici.twig`

### Expected Result

Report as error.

### Correct

- `New non-English filename introduced by the current change should be renamed to an English filename.`

### Incorrect

- `Allowed because file content is otherwise valid.`

## Example Group 15: Touched File But Unrelated Legacy Debt Elsewhere

### Case

A user edits `templates/admin/mod_content/list.twig`.

Another untouched file `templates/admin/mod_video/list.twig` contains Turkish strings and comments.

### Expected Result

Do not report the untouched file.

### Correct

- `No finding for unrelated untouched file.`

### Incorrect

- `Because one file was touched, the agent should report repository-wide language debt.`

## Final Rule Of Thumb

Ask these questions in order:

1. Is the file touched?
2. If no, stay silent unless a dedicated legacy audit was requested.
3. If yes, is the issue a non-English comment or non-English hard-coded user-facing string?
4. If yes, report it even if it predates the current diff.
5. If no, keep the check diff-scoped.

