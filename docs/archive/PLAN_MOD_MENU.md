# Plana Modernîzekirina Modula Menuyê (mod_menu)

## Armanc
Veguherandina modula menuyê ya kevin (`templates/admin_old/mod_menu/`) bo guhertoyek nû, modern û mobîl-dost (`templates/admin/mod_menu/`). 
Ev modul divê rê bide bikarhêneran ku menuyan bi hêsanî lê zêde bikin, biguherînin û bi awayekî "drag-and-drop" (kişandin û berdan) rêz bikin (Tree View).

## Rewşa Niha (Analîza `templates/admin_old/mod_menu/`)
Dosyayên heyî:
*   `listele.twig`: Lîsteya menuyan nîşan dide.
*   `ekle.twig`: Forma lêzêdekirina menuyê ye.
*   `duzenle.twig`: Forma guherandina menuyê ye.
*   `script.twig` & `css.twig`: Kodên JS û CSS yên kevin (Nestable jQuery plugin).

## Pêngavên Pêşveçûnê (Roadmap)

### 1. Amadekarî û Binesazî
*   Peldanka `templates/admin/mod_menu/` hat avakirin.
*   Di vê peldankê de divê em dosyayên nû biafirînin: `listele.twig`, `ekle.twig`, `duzenle.twig`.

### 2. Modernîzekirina Lîsteyê (`listele.twig`)
*   **Design:** Em ê sêwirana Material Design 3 (M3) bikar bînin. Card layout ji bo lîsteyê.
*   **Teknolojî:** Ji şûna "Nestable" ya kevin, em ê pirtûkxaneyek modern a JS mîna `SortableJS` an jî versiyonek nû ya `Nestable2` bikar bînin ku bi mobîlê re baş dixebite.
*   **Taybetmendî:**
    *   Dîtina daran (Tree structure) ji bo menuyên bin-asta (sub-menus).
    *   Bişkokên çalakiyan (Action buttons): Guherandin, Jêbirin, Çalak/Neçalakirin.
    *   Piştgiriya Drag-and-drop ji bo guherandina rêzê.

### 3. Forma Lêzêdekirinê (`ekle.twig`)
*   **Design:** Formek paqij û rêkûpêk. Dibe ku em "Wizard" an "Modal/Drawer" bikar bînin, lê ji bo menuyan formek standard jî baş e.
*   **Zeviyên Pêwîst (Fields):**
    *   Navê Menuyê (Multilingual heke hebe).
    *   Link/URL.
    *   Parent Menu (Menuya jorîn).
    *   Rêz (Order).
    *   Target (_blank, _self).
    *   Icon (bijartina icon).

### 4. Forma Guherandinê (`duzenle.twig`)
*   Mîna `ekle.twig`, lê bi daneyên heyî dagirtî.

### 5. JavaScript û CSS
*   Afirandina `script.twig` ya nû ji bo rêvebirina drag-and-drop û AJAX requestan.
*   Bikaranîna SweetAlert2 ji bo erêkirina jêbirinê.
*   Bikaranîna Tom Select ji bo hilbijartina Parent Menu.

## Têbînîyên Teknîkî
*   **AJAX:** Hemû guherandinên rêzê (sorting) divê bi AJAX tavilê werin tomarkirin.
*   **Icon:** Em ê îkonên modern (mîna Material Symbols an FontAwesome) bikar bînin.
*   **Responsiveness:** Divê lîste li ser telefonan jî baş xuya bike û were bikaranîn.

## Destpêk
Em ê pêşî bi analîzkirina koda kevin ya `listele.twig` dest pê bikin û dûv re versiyona nû binivîsin.
