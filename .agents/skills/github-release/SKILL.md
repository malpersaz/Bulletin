---
name: github-release
description: GitHub Upload & Release Process - Validate version parity across Git history, config/site.config.php, and docs/VERSION.md before performing GitHub release uploads.
---

# GitHub Upload & Release Process (GitHub Release Skill)

Github Protection of the Release Development Process Code:
- The release code under development is shown as "In Progress" in the docs/VERSION.md file.
- The same code is also present in the config/site.config.php file.

1. When a user requests updates to be uploaded to Github, review the history and check previously uploaded versions. The most recently uploaded version must be -1 less than the In Progress version. Otherwise, stop the process and warn the user!
2. If there are no errors, write today's date in the "In Progress" field in the docs/VERSION.md file.
3. Then upload the updates to Github.
4. The Github upload process is complete.
