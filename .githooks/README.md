# Git Hooks

This repository uses a tracked `pre-commit` hook in this directory.

Enable it locally with:

```bash
git config core.hooksPath .githooks
chmod +x .githooks/pre-commit
```

The hook blocks commits that stage repository changes without also staging `docs/VERSION.md`.
