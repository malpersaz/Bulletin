# Plana Nirxandina Dijberî ya Dor 2 (Round 2 Adversarial Review Plan)

## Armanc
Kontrolkirina kûr û ceribandina sîstematîk a tevahiya kodê ji bo parastina sînorên repoyê (Zero commercial module references), piştrastkirina hêza testên statîk û E2E, û xebitandina 100% bê kêmasî ya testan.

## Pêngavên Nirxandinê (Review Steps)
1. **Kontrola Kûr a Referansan (Deep Boundary Audit):**
   - Piştrastkirina 0 referansên `mod_client`, `mod_service`, `mod_product`, `mod_reference` û kategoriyên wan li seranserê repoyê.
2. **Hêzkirina Testên Ewlehiyê (Static Auditor Hardening):**
   - Zêdekirina hemû 7 guherbarên modulên bazirganî bo `StaticAuditor` û `Tier0QualityContractsTest.php`.
3. **Kontrolkirina Modulên Modernîzekirî (Modernized Modules Audit):**
   - Piştrastkirina wiring û tenduristiya `mod_content_category` di `mod/management/index.php` de.
4. **Pêkanîna Hemû Testên Xweser (Complete Test Suite Execution):**
   - E2E Test Suite (`php tests/e2e/run.php`)
   - Adversarial Suites (Twig, M1, M2, Toggle Permission Simulator, Registry Stress, Run & Validator)
   - CSS Touch Target Auditor (`python3 tests/m2_css_touch_target_auditor.py`)
   - Runtime Tests (`php tests/module_management_runtime_test.php`)
   - PHP (`php -l`) û JavaScript (`node --check`) syntax linting
5. **Belgekirina Rapora Dawî (Final Handoff Report):**
   - Amadekirina û şandina rapora nirxandinê bi riya `send_message`.
