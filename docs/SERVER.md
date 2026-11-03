# Rênîşandana Servera Bulletin CMS (SERVER.md)

- **Guherto (Version):** `v1.0.19` (2026-11-03)
- **Malper:** `amedbulten.com`
- **Server:** `ssh-w02232db@w02232db.kasserver.com` (ALL-INKL KAS)
- **Pelrêça Giştî ya Webê (DocumentRoot):** `/www/htdocs/w02232db/amedbulten.com/`

---

## 1. Kurteya Guhertoyê (v1.0.19 Overview)

Di guhertoya **v1.0.19** de hemû pelrêça sereke ya serverê (web root) ji pelên pêşvebirinê, metadataya Git, belgeyên navxweyî, test û pelên zêde hate paqijkirin. Her wiha sîstemeke nûjen a weşandinê (deployment) hate sazkirin da ku tiştên li ser GitHubê hene jî, dema kod dakeve ser serverê, **qet danekevin ser serverê û li wir tune bin**.

### Armancên Sereke yên hatin bicihanîn:
1. **Paqijkirina Root-ê:** Kêmkirina pelrêçan ji 19an daxistina **11** pelrêçên pêwîst, û kêmkirina pelan ji 28an daxistina **13** pelên pêwîst.
2. **Rakirina Xetereyên Ewlehiyê:** Rakirina pelrêça `.git/` (33 MB) û `.agents/` (20 MB) ji ber çavê webê.
3. **Parastina Daneyên Serverê:** Parastina pelên krîtîk ên wek `config/*.local.php`, `data/*.json`, wêneyên nûçeyan di `static/mod_*`, û pêşbîran.
4. **Sîstema Weşandina Zîrek:** Afirandina `.deployignore` û skrîpta `tools/deploy_production.sh`.
5. **Zexmkirina Apache `.htaccess`:** Astengkirina rasterast a pelên `.md`, `.json`, `.lock`, `.sql`, `.sh` û pelên veşartî (`.`).

---

## 2. Anatomî û Struktura Root-a Serverê (Clean Root State)

Piştî guhertoya v1.0.19, tenê pel û pelrêçên jêrîn li ser serverê cih digirin:

### 2.1 Pelrêçên Çalak ên li ser Serverê (11 Pelrêç)

```text
/www/htdocs/w02232db/amedbulten.com/
├── cache/          # Pêşbîra Twig û pergalê (destûra nivîsandinê heye)
├── config/         # Mîhengên pergalê û databasê (bi .htaccess hatiye parastin)
├── data/           # Daneyên verastkirinê û JSON (bi .htaccess hatiye parastin)
├── lib/            # Pirtûkxaneyên koda PHP ya navikî (Framework Core)
├── logs/           # Tomarên çewtiyan ên pergalê (Error logs)
├── mod/            # Modulên malperê (mod_news, mod_event, mod_page, hwd.)
├── static/         # Pelên giştî (CSS, JS, Font, û medyayên modulan)
├── tasks/          # Karên periyodîk ên cronê yên hilberînê (Cron tasks)
├── templates/      # Şablonên Twig (amed, admin)
├── templates_c/    # Pêşbîra berhevkirî ya şablonan
└── vendor/         # Paketên sîstemê yên Composer (tenê --no-dev)
```

### 2.2 Pelên Çalak ên li ser Root-a Serverê (13 Pel)

| Pel | Erka li ser Serverê |
| :--- | :--- |
| `.htaccess` | Rêgezên Apache, ewlehî, mîhengên lezkirinê û URL rewrites |
| `captcha.php` | Hilberînerê wêneyê koda ewlehiyê (Captcha) |
| `clear_cache.php` | Alîkarê paqijkirina pêşbîrê ya panelê |
| `css.php` | Kêşandin û hevgirtina stîlên CSS |
| `index.php` | Deriyê sereke yê têketina pergalê (Front Controller) |
| `init.php` | Destpêkirina bingehîn a frameworkê |
| `js.php` | Kêşandin û pêşkêşkirina skrîptên JavaScript |
| `llms.php` | Xala belavkirina naverokê ji bo botên AI (`llms.txt`) |
| `mcp.php` | Xala girêdana servîsa MCP |
| `modules.php` | Rêvebir û barkêşê modulan |
| `resize.php` | Guhertina pîvanên wêneyan bi awayekî lezgîn û otomatîk |
| `robots.php` | Afirandina dînamîk a rêgezên Spider û Crawleran |
| `robots.txt` | Pelê destûr û qedexeyên motorên lêgerînê |
| `template.php` | Alîkarê barkirina şablonan |

---

## 3. Lîsteya Tiştên ku ji Serverê Hatin Rakirin (Deleted from Server)

Ev pel û pelrêç **tenê li ser GitHubê dimînin** û di servera zindî de bi tevahî hatin jêbirin:

| Pel / Pelrêç | Mezinahî | Sedema Rakirinê |
| :--- | :--- | :--- |
| `.git/` | ~33 MB | Xetereya krîtîk a ewlehiyê; kod û dîrok ji derve re vekirî bû. |
| `.agents/` | ~20 MB | Agahiyên amûrên AI û daneyên pêşvebirinê yên herêmî ne. |
| `.gemini/` | ~12 KB | Mîhengên pêşvebirinê yên Gemini. |
| `docker/` | ~32 KB | Mîhengên container ên herêmî ne (MySQL, PHPMyAdmin Docker). |
| `docs/` | ~340 KB | Belgeyên navxweyî û planên pêşvebirinê ne. |
| `tests/` | ~404 KB | Testên yekîneyê yên dema pêşvebirinê ne. |
| `tools/` | ~156 KB | Skrîptên Python yên çavdêrî û lîntkirinê ne. |
| `installer/` | ~1.1 MB | Sazkera databasê ye; piştî sazkirinê mayîna wê xetere ye. |
| Hemû pelên `*.md` | ~35 KB | `AGENTS.md`, `CODE_QUALITY_AGENT_STRATEGY.md`, `DESIGN.md`, `PILOT_AGENT_REVIEW_FLOW.md`, `PROJECT.md`, `TEST_INFRA.md`, `TEST_READY.md`. |
| `docker-compose.yml` | 2 KB | Mîhengên Docker ên komputera herêmî. |
| `package.json` | 56 B | Mîhengên paketên NPM. |
| `package-lock.json` | 2 KB | Kilîta guhertoyên NPM. |
| `rector.php` | 1 KB | Amûra refactoring a PHP; di hilberînê de qedexe ye. |
| `composer.json` | 1 KB | Agahiyên versiyonên koda derva di web root de xetere ye. |
| `composer.lock` | 28 KB | Kilîta Composer; ji derve re nayê nîşandan. |
| `.gitignore` | 1 KB | Tenê ji bo amûra Git pêwîst e. |

---

## 4. Rêbaza Weşandinê ya Nû (Deployment Workflow)

Ji niha û pê ve, fermanên `git clone` an `git pull` **qet rasterast li ser serverê di nav `amedbulten.com` de nayên bikaranîn**.

### 4.1 Çawa Guhertin Têne Şandin?

1. **Guhertinên xwe bişînin GitHubê:**
   ```bash
   git add .
   git commit -m "feat/fix: peyama guhertinê"
   git push origin main
   ```
2. **Bi skrîpta xweser bişînin ser Serverê:**
   Li ser komputera herêmî (an bi rêya AGY):
   ```bash
   bash tools/deploy_production.sh
   ```
   *(Ji bo ceribandinê bêyî guhertin: `bash tools/deploy_production.sh --dry-run`)*

### 4.2 Skrîpta Weşandinê Çawa Kar Dike?
- Pelê `.deployignore` dixwîne û hemû pelên zêde (.git, .agents, docs, tests, tools, *.md) paşguh dike.
- Pelên nû û guhertî bi rêya `rsync` bi lezgînî dişîne serverê.
- Pelên taybet ên serverê (`config/*.local.php`, `data/*.json`, `static/mod_*`) diparêze û qet dest nade wan.
- Piştî şandinê, pêşbîra Twig (`cache/twig/*`) li ser serverê paqij dike.
- Kontrola tenduristiyê (`curl https://amedbulten.com/`) dike û rewşa HTTP 200 radigihîne.

---

## 5. Mîhengên Ewlehiyê yên Apache (`.htaccess`)

Di pelê `.htaccess` de sê astên nû yên mertalê ewlehiyê hatin zêdekirin:

1. **Astengkirina Pelên Veşartî:**
   ```apache
   <FilesMatch "^\.">
     Require all denied
   </FilesMatch>
   ```
2. **Astengkirina Pirtûkxaneyan û Pelên Hesas:**
   ```apache
   <FilesMatch "\.(md|markdown|json|lock|yml|yaml|dist|sql|sqlite|log|sh|bak|backup|swp)$">
     Require all denied
   </FilesMatch>
   ```
3. **Parastina Pelrêça Mîhengan (`config/.htaccess`):**
   Di pelrêça `config/` de pelek `.htaccess` heye ku rasterast gihiştina hemû pelan ji derve qedexe dike (`Require all denied`).
4. **Îstîsnaya Giştî:**
   Pelê `site.webmanifest` bi rêbaza `<Files "site.webmanifest"> Require all granted </Files>` vekirî hatiye hiştin da ku cîhazên mobîl bikaribin nîşan bikin.

---

## 6. Fermanên Piştgirî û Kontrolê yên Serverê

Heke hûn bi rêya SSH têkevin nav serverê (`ssh ssh-w02232db@w02232db.kasserver.com`):

- **Kontrolkirina root-ê:**
  ```bash
  ls -la /www/htdocs/w02232db/amedbulten.com/
  ```
- **Paqijkirina destî ya pêşbîrê (Cache Clear):**
  ```bash
  rm -rf /www/htdocs/w02232db/amedbulten.com/cache/twig/*
  rm -rf /www/htdocs/w02232db/amedbulten.com/templates_c/*
  ```
- **Kontrolkirina tomarên çewtiyê (Logs):**
  ```bash
  tail -n 50 /www/htdocs/w02232db/amedbulten.com/logs/error.log
  ```

---

## 7. Kontrola Qsûr û Kêmasiyan (Defect Audit & Resolutions)

Di kontrola berfireh a pergala zindî de ev kêmasî hatin dîtin û bi temamî hatin çareserkirin:

1. **Çewtiya `view_count` di Databasê de (`mod_page`, `mod_publication`):**
   - **Kêmasî:** Dema rûpelek ji `/page/` an gotarek ji `/publication/` dihate vekirin, ji ber ku stûna `view_count` di wan tabloyên databasê de tune bû, `DatabaseException: Column not found: 1054 Unknown column 'view_count'` dihat tomarkirin.
   - **Çareserî:** Stûna `view_count INT(11) NOT NULL DEFAULT 0` li ser her du tabloyên databasê hate zêdekirin. Her wiha di koda `_update_view_count()` de (`indexMain.php` û `indexBase.php`) bloka ewlehiyê ya `try ... catch` hate bicihkirin da ku tu carî pergalê nexe xetayê.
2. **Çareserkirina Rûpela Kurmancî (`https://amedbulten.com/ku/`):**
   - **Kêmasî:** Zimanê Kurmancî di tabloya `lang` a databasê de bi `status = 'n'` rawestiyabû; loma dema kesek diçû `/ku/`, routerê ew wekî modul fam dikir û xetaya `"ku modülü bulunamadı"` dida.
   - **Çareserî:** Di tabloya `lang` de rewşa `ku` hate çalakirin (`status = 'y'`). Niha rûpela sereke ya Kurmancî bi rewşa **HTTP 200 OK** bêkêmasî dixebite.
3. **Pêşîlêgirtina Xetayên 404 li ser Navên Modulan (`/news`, `/publication`, `/event`):**
   - **Kêmasî:** Heke bikarhêner rasterast biçûya navnîşanên kurt ên mîna `/news`, `/publication`, `/event`, ji ber nebûna rêgeza rasterast 404 dida.
   - **Çareserî:** Di `config/url.config.php` de rêgezên kurt rasterast ber bi lîsteyên wan ve hatin girêdan.
4. **Paqijkirina Tomarên Çewtiyan (Zero Error Log State):**
   - Pelê `logs/ajax_errors.log` hate kontrolkirin û hemû daxwazên nû yên malperê bi 0 çewtî dixebitin.
