# Plana Nûjenkirina Modula Kategoriyan (V2)

Li ser daxwaza te, min plana xwe nû kir. Ez ê sêwirana "Mobile-Only" biparêzim û kodên kevn (`templates/admin_old/mod_category`) wekî çavkanî bikar bînim.

## 1. Analîza Kodên Kevn (`templates/admin_old`)
Min kodên kevn lêkolîn kirin û ev taybetmendiyên girîng dîtin ku di pergala nû de kêm in:
*   **Otomasyona SEO (JS):** Di kodên kevn de `make_seo`, `make_title`, `make_keywords` hene ku dema nav tê nivîsandin, qadên din otomatîk dagirtin.
*   **Validation:** Kontrola `validationEngine` hatibû bikaranîn.
*   **Rêveberiya Wêne û Menuyan:** Di formê de vebijarkên ji bo menu û wêneyan hebûn.

## 2. Lîsteya Erkên Nû (Roadmap)

### Gava 1: Çareseriya Database (Acîl)
*   **Pirsgirêk:** Stûna `sira` di databasê de vala namîne û dibe sedema "Crash".
*   **Çareserî:** Ez ê di koda PHP de (`mod/mod_category/index.php`) dema tomar kirinê, nirxê `sira` bixweber hesab bikim an jî di databasê de nirxê `DEFAULT 0` bidimê.

### Gava 2: Nûjenkirina Forma Kategoriyê (Smart Form)
Ez ê forma `templates/admin/mod_category/ekle.twig` (û `duzenle.twig`) nû bikim:
*   [ ] **SEO Otomasyon:** Mîna kodên kevn, dema "Nav" tê nivîsandin, "SEO URL" û "Sernav" dê otomatîk bên çêkirin.
*   [ ] **Wêne (Image Upload):** Ez ê qada wêneyê lê zêde bikim (û pêşdîtina wê).
*   [ ] **Edîtor:** Ji bo "Danasîn" (Content) edîtorê TinyMCE an Summernote (li gorî standarda nû) lê zêde bikim.
*   [ ] **Validasyon:** Heke nav vala be, destûr nayê dayîn.

### Gava 3: Pêşxistina Lîsteyê (Mobile-First UX)
Li şûna sêwirana Desktop, ez ê sêwirana Mobîl xurt bikim:
*   [ ] **Sticky Header:** Sernav û bişkojkên lêgerînê dê li jor sabit bimînin da ku bikarhêner winda nebe.
*   [ ] **Hêsankirina Lîsteyê:** Lîste dê paqijtir bibe, agahiyên zêde (wekî ID) werin veşartin an biçûkkirin.
*   [ ] **Action Menu:** Menûya "Sê Xal" dê hêsantir bibe.

### Gava 4: Ferman û Kontrol (Delete & Feedback)
*   [ ] **SweetAlert2:** Ji bo jêbirinê ("Tu piştrast î?" - "Are you sure?") û peyamên serkeftinê.

Niha ez dest bi **Gava 1 (Database Fix)** û **Gava 2 (Smart Form)** dikim.
