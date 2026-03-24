# Research Plan: Mobile Slider Issue Analysis

## Objective
To systematically identify and resolve the issue where the homepage slider does not display correctly (specifically regarding the mobile and desktop image scaling and cropping) when viewed in mobile emulation (F12) mode.

## 1. Viewing the Website in F12 Mode via Playwright MCP Server
To accurately simulate the mobile viewing experience (F12 developer tools mode) utilizing the Playwright MCP Server, the following tools/components are required:

*   **Playwright MCP Server:** The Model Context Protocol server integrated into the IDE that exposes robust browser automation and device emulation capabilities.
*   **Chromium/Browser Engine:** The underlying headless browser engine managed by Playwright to render the DOM and accurately emulate specific mobile hardware (e.g., iPhone 12/13 with exact viewport dimensions, device scale factor, and touch support).
*   **IDE Context/Agent Tools:** The internal AI tools (like `run_command` or specific MCP tool calls) required to interface with the Playwright server, execute the navigation to `http://localhost:8080`, and capture visual feedback (screenshots/DOM snapshots).

## 2. Additional Tools Needed Besides Playwright
To perform a comprehensive root-cause analysis, the following tools are also necessary:

*   **Version Control (Git):** To clean and reset the workspace to your exact pristine baseline, ensuring no conflicting AI-generated code obscures the original problem.
*   **File System Inspection Tools (`view_file`, `list_dir`, `grep_search`):** To deeply analyze the raw CSS geometries and HTML DOM structures.
*   **Network/HTTP Clients (`curl`):** To make headless requests to the Docker container, verifying if issues stem from server-side caching (Nginx/PHP), Twig template caching, or browser-side caching.
*   **Image Analysis Utilities (e.g., `file` or `imagemagick`):** To extract the exact dimensions and aspect ratios of `image_mobile` and `slide.image` so the CSS can be mathematically aligned to prevent arbitrary clipping by `object-fit`.

## 3. Files to be Evaluated
To successfully diagnose the intersection of responsive design and image rendering, we must evaluate the following core files:

*   **Layout & HTML Structures:**
    *   `templates/soul/index/mod_slider/slider.twig`: Critical for analyzing the `<picture>` element and precisely how the `<source media="(max-width: 767px)">` breakpoint swaps the images.
    *   `templates/soul/layouts/base.twig`: To verify the `<meta name="viewport">` scaling behavior and the CSS compilation/versioning strings (`?v=...`).
*   **Style Definitions:**
    *   `templates/soul/static/css/soul-shell.css`: To inspect core theme mechanics, specifically `.soul-slider`, `100vh`/`100dvh` behaviors, and `overflow` rules.
    *   `templates/soul/static/css/custom.css`: To analyze user-defined breakpoints, specific pixel heights, and overrides that dictate mobile vs. desktop rendering.
*   **Environment & Build Systems:**
    *   `docker-compose.yml`: To confirm volume mappings (ensuring live edits reflect in the container).
    *   `templates/soul/static/Gruntfile.js`: To verify that direct modifications to CSS files aren't bypassed or ignored by an underlying build process compiling `.less` or `.scss` files.

## 4. Git Configuration & Ignored Files
During the installation and research process, the following dependencies and temporary directories must not be committed to Git. They should be added to `.gitignore`:
*   `node_modules/` - Third-party dependencies and package installations.
*   `.playwright/` or any temporary testing browser directories.
*   `*.log` - Debug and error logs.
*   `.env` - Secret environment configuration files.
*   `.cache/` or `.sass-cache/` - Temporary build caching output.
