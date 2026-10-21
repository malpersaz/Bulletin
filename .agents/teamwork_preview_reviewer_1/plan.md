# Plana Nirxandina Dijberî (Adversarial Review Plan)

## Armanc
Kontrolkirin û ceribandina kûr a hemû guhertinên ku ji bo jêbirina referansên modulên kevnare (`mod_client`, `mod_service`, `mod_product`, `mod_reference`) di repoya `Bulletin` de hatine kirin.

## Pêngavên Nirxandinê (Review Steps)
1. **Lêgerîn û Kontrola Statîk (Static Audit & Grep):**
   - Kontrolkirina koda çavkaniyê di peldanka `mod/` de ji bo hebûna referansên van modulan.
   - Kontrolkirina peldankên `templates/`, `lib/`, `installer/`, `config/`, û `inc/`.
2. **Kontrola Testên E2E û Parastinê (Test Wiring Verification):**
   - Kontrolkirina `StaticAuditor::checkDeprecatedModuleReferences()` di `Tier0QualityContractsTest.php` û `Tier1FeatureCoverageTest.php` de.
   - Piştrastkirina ku test nehatine qutkirin an manîpulekirin.
3. **Pêkanîna Hemû Testan (Test Suite Execution):**
   - Xebitandina `tests/e2e/run.php`
   - Xebitandina hemû testên adverserial ên din (`twig_template_adversarial_test.php`, `m1_adversarial_challenge_test.php`, `m2_adversarial_challenge_test.php`, `registry_adversarial_stress_test.php`, hwd.)
   - Kontrolkirina lînt û hevgirtina ziman (i18n guard û comment guard).
4. **Çareserkirina Çewtiyan (Defect Fixing):**
   - Heke kêmasî an şaşiyek hebe, sererastkirina yekser û kêm-guhertin (minimal diff).
5. **Rapora Dawî (Final Verdict):**
   - Şandina rapora standard a nirxandinê bi riya `send_message`.
