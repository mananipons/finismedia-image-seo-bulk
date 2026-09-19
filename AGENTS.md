# AGENTS.md — Finismedia Image SEO Bulk Editor

> Fitxer de context per a agents LLM (Claude Code, Antigravity IDE, etc.) que treballin
> en aquest repositori. Llegeix-lo sencer abans de fer cap canvi.

## 1. Qui és el client d'aquest projecte

**Finismedia** (finismedia.com) és una agència catalana de desenvolupament web i
digitalització, ubicada al Vallès Oriental. Activitat principal:

- Desenvolupament i manteniment de webs WordPress (Elementor, WooCommerce).
- Auditories i optimització SEO.
- Exploració de productes SaaS per a petit comerç local.

L'stack habitual dels clients de Finismedia: WordPress + Elementor (Pro) + WooCommerce +
Polylang/WPML/Weglot + WP Rocket + SiteGround/Plesk. Aquest plugin s'ha de mantenir
compatible amb aquest ecosistema.

## 2. Què és aquest repositori

Un plugin de WordPress: **Finismedia Image SEO Bulk Editor** (`fisb`).

**Objectiu**: permetre editar en lot els atributs `ALT` i `Títol` de totes les imatges
de la mediateca d'un client, amb generació automàtica opcional de text, i amb
compatibilitat **configurable** amb WPML (no assumeix un únic comportament fix).

**Per què existeix**: és una eina interna de Finismedia per accelerar les auditories i
correccions SEO d'imatges en projectes de client (veure metodologia SEO a la secció 6),
que actualment és un dels punts febles recurrents detectats a les auditories (imatges
sense ALT, títols buits, inconsistència entre idiomes a sites WPML).

## 3. Estructura del plugin

```
finismedia-image-seo-bulk/          # arrel del repo (no és un plugin WP en si)
├── AGENTS.md
└── plugin/                          # carpeta que es distribueix/instal·la com a plugin
    ├── finismedia-image-seo-bulk.php  # Bootstrap: capçalera del plugin, includes, init
    ├── includes/
    │   ├── class-fisb-generator.php   # Lògica de generació de text ALT/Títol
    │   ├── class-fisb-wpml.php        # Detecció i sincronització WPML (opcional)
    │   ├── class-fisb-settings.php    # Settings API (mètode per defecte, plantilla, mode WPML)
    │   ├── class-fisb-list-table.php  # WP_List_Table: llistat, filtres, accions en lot
    │   ├── class-fisb-admin.php       # Menús admin, render de pàgines, enqueue d'assets
    │   └── class-fisb-ajax.php        # Endpoints AJAX: desat individual i generació
    ├── assets/
    │   ├── admin.js                   # Autosave inline (AJAX), generació per fila
    │   └── admin.css                  # Badges d'estat, estils del formulari
    ├── readme.txt                     # Format estàndard de readme de plugin WP
    └── plugin.zip                     # build zipejat (generat, no editar a mà —
                                        # revisar si cal regenerar-lo abans de lliurar canvis)
```

> **Important**: tots els fitxers de codi viuen dins `plugin/`, no a l'arrel del repo.
> El repo és un repositori git publicat a GitHub. `README.md` documenta el plugin de
> cara a usuaris; mantén-lo sincronitzat amb `plugin/readme.txt` en canviar funcionalitats.
> Els `.zip` de build estan al `.gitignore` (no es versionen).

### Responsabilitat de cada classe

- **`FISB_Generator`** (estàtica, sense estat): tres mètodes de generació —
  `from_filename()` (neteja el nom de fitxer), `from_context()` (busca el post/pàgina
  que fa servir la imatge, com a destacada o dins del contingut), `from_template()`
  (placeholders `{filename}`, `{post_title}`, `{site_name}`).
- **`FISB_WPML`**: totes les crides a l'API de WPML (`icl_object_id`,
  `wpml_post_language_details`, `wpml_element_trid`, `wpml_get_element_translations`)
  estan encapsulades aquí. **Cap altra classe ha de cridar directament funcions de
  WPML** — sempre a través d'aquesta classe, perquè el plugin funcioni igual (sense
  errors) en sites sense WPML instal·lat.
- **`FISB_Settings`**: una única opció `fisb_settings` (array) amb `default_method`,
  `template`, `wpml_sync_mode` (`independent` / `sync` / `ask`).
- **`FISB_List_Table`**: extén `WP_List_Table` natiu de wp-admin. Filtres per estat
  (falta ALT / falta Títol / falta qualsevol) i idioma. Accions en lot processades a
  `process_bulk_action()`.
- **`FISB_Admin`**: registra dues pàgines sota `Mèdia` (`fisb-images` i
  `fisb-settings`), encarregada dels `wp_localize_script` amb el que necessita el JS.
- **`FISB_Ajax`**: `fisb_save_field` (desa ALT o Títol d'un adjunt) i `fisb_generate`
  (retorna un suggeriment sense desar-lo, el JS decideix si el desa).

## 4. Decisions de disseny clau (no canviar sense motiu)

1. **No es crea cap taula nova a la BD.** Es reutilitzen `_wp_attachment_image_alt`
   (postmeta) i `post_title` (natiu de l'adjunt). Decisió deliberada per mantenir el
   plugin lleuger i sense migracions.
2. **La sincronització WPML és sempre opt-in i configurable per l'usuari**, mai
   automàtica per defecte sense que l'admin ho hagi triat. Motiu: cada client de
   Finismedia fa servir WPML de manera diferent (alguns amb Media Translation activada,
   altres amb mèdia compartit entre idiomes) i forçar un comportament trencaria sites.
3. **Desat via AJAX per camp individual** (`change` event a l'input), no per formulari
   sencer. Les accions en lot sí que fan submit tradicional (recarrega la taula), és el
   patró estàndard de `WP_List_Table` i evita reinventar paginació/nonces per AJAX.
4. **Detecció de context** (`find_referencing_post`) prioritza: (1) imatge destacada
   via `_thumbnail_id`, (2) referència `wp-image-{ID}` al contingut. És una heurística
   simple i ràpida (consulta SQL directa), no un escaneig complet de blocs.
5. **`FISB_Generator::generate()` és agnòstic del camp destí** (ALT o Títol): retorna
   sempre un text; qui l'ha de desar decideix on va (`_wp_attachment_image_alt` vs
   `post_title`). Des de v1.1.0 la generació (per nom / context / plantilla) està
   disponible tant per ALT com per Títol, tant per fila (`.fisb-generate` amb
   `data-field="alt|title"` a `admin.js`) com en accions en lot
   (`generate_title_filename|context|template` a `FISB_List_Table`).
6. **Mai usar `get_the_title()` (ni cap altra funció que passi pel filtre `the_title`)
   per generar un valor que després es desa a la BD.** `wptexturize` hi converteix
   l'apòstrof en l'entitat literal `&#8217;` i, amb la configuració d'idioma català,
   afegeix cometes tipogràfiques «»; el resultat és per imprimir sense escapar, no per
   re-desar. `FISB_Generator::context_title()` (v1.1.1+) llegeix `post_title` cru via
   `get_post()`. Si mai es necessita un títol per *mostrar* (no desar), aleshores sí
   que `get_the_title()` és correcte (p. ex. la columna "S'utilitza a" de
   `FISB_List_Table`).

## 5. Limitacions conegudes / deute tècnic

- **Paginació inexacta amb filtres actius**: quan `fisb_status` o `fisb_lang` estan
  actius, el filtratge es fa *després* de la consulta `WP_Query` (a nivell de PHP, no
  SQL), així que `found_posts` reflecteix el total sense filtrar. Amb mediateques
  grans (>500 imatges) cal migrar el filtre d'estat a un `meta_query` real i el
  d'idioma a un `JOIN` amb la taula de traduccions de WPML.
- **No hi ha tests automatitzats.** Si s'amplia el plugin, prioritzar tests per
  `FISB_Generator` (és pura lògica, fàcil de testejar) abans que per les classes
  d'admin/AJAX.
- **No validat amb `php -l` en CI** — el sandbox de desenvolupament original no tenia
  PHP disponible; només es va comprovar el balanç de claus/parèntesis. **Primer pas en
  qualsevol sessió de treball: validar sintaxi amb `php -l` a tots els fitxers.**
- **`sanitize_text_field()` a l'ALT**: si en el futur es vol permetre ALT amb salts de
  línia o HTML mínim, cal revisar-ho (actualment s'elimina tot).
- **`plugin/plugin.zip`** és un build zipejat, no es regenera automàticament. Si es
  toquen fitxers de `plugin/`, cal refer el zip (o esborrar-lo) abans de lliurar-lo a
  un client, perquè no quedi desincronitzat amb el codi font.

## 6. Metodologia SEO "Expert SEO Universal" (context, no part del codi)

Aquest plugin és una eina de suport a les auditories SEO de Finismedia. Quan es
treballi en features noves relacionades amb SEO (per exemple, ampliar aquest plugin
amb detecció de problemes o generar informes), cal seguir aquesta metodologia:

### Comandes d'auditoria (per a ús conversacional amb l'usuari, no rutes del plugin)

| Comanda | Anàlisi |
|---|---|
| `audit <url>` | Auditoria completa (tècnic + contingut + schema + imatges + GEO) |
| `page <url>` | Anàlisi en profunditat d'una sola pàgina |
| `technical <url>` | Crawlabilitat, indexació, seguretat, Core Web Vitals |
| `content <url>` | E-E-A-T i qualitat de contingut |
| `schema <url>` | Detecció, validació i generació de Schema.org |
| `images <url>` | Optimització d'imatges i alt texts |
| `sitemap <url>` | Anàlisi o generació de sitemap XML |
| `geo <url>` | Optimització per a IA Overviews, ChatGPT, Perplexity |
| `local <url>` | Local SEO (GBP, NAP, citacions, ressenyes) |
| `plan <tipus-negoci>` | Pla estratègic SEO (saas, local, ecommerce, publisher) |
| `hreflang <url>` | Auditoria d'etiquetes hreflang i i18n |
| `cluster <keyword>` | Clustering semàntic i arquitectura de contingut |
| `backlinks <url>` | Anàlisi del perfil de backlinks |
| `ecommerce <url>` | SEO per a botigues (schema de producte, marketplace) |

### Detecció de tipus de negoci

- **SaaS**: pàgina de preus, `/features`, "free trial", `/docs`.
- **Local**: telèfon, adreça, àrea de servei, Google Maps.
- **E-commerce**: `/products`, `/cart`, "add to cart", schema de producte.
- **Publisher**: `/blog`, `/articles`, schema d'article, pàgines d'autor.
- **Agència**: `/case-studies`, `/portfolio`, logos de clients.

### SEO Health Score (0–100)

| Categoria | Pes |
|---|---|
| Contingut i E-E-A-T | 23% |
| SEO Tècnic | 22% |
| On-Page SEO | 20% |
| Schema / Dades estructurades | 10% |
| Rendiment (Core Web Vitals) | 10% |
| Preparació per a IA (GEO) | 10% |
| Imatges | 5% |

### Nivells de prioritat

- **Crític**: bloqueja indexació o causa penalitzacions → fix immediat.
- **Alt**: impacta significativament el posicionament → fix en 1 setmana.
- **Mitjà**: oportunitat d'optimització → fix en 1 mes.
- **Baix**: millora desitjable → backlog.

### Regles fixes (mai incomplir-les)

- Core Web Vitals: sempre **INP** (mai FID, eliminat el setembre 2024).
  LCP < 2.5s | INP < 200ms | CLS < 0.1.
- Schema `HowTo`: **deprecated** (setembre 2023) → no recomanar mai.
- Schema `FAQ`: només per a webs governamentals i de salut (restricció agost 2023).
- E-E-A-T: aplicar els Quality Rater Guidelines actualitzats a setembre 2025.
- Pàgines de localització: warning a 30+, stop a 50+ sense justificació.
- Mai inventar dades d'una URL no accessible; indicar explícitament què no es pot
  verificar.

### Format de sortida d'una auditoria

1. Resum executiu amb el SEO Health Score (X/100).
2. Taula de problemes per prioritat (Crític → Alt → Mitjà → Baix).
3. Anàlisi per categories (les rellevants segons el tipus de negoci detectat).
4. Pla d'acció prioritzat amb passos concrets.

## 7. Context tècnic de l'ecosistema Finismedia (útil si s'amplia el plugin)

Coses apreses treballant amb clients reals que poden afectar aquest plugin si s'ha
d'integrar amb altres eines del stack:

- **WP Rocket + Elementor**: Delay JS ha d'excloure explícitament
  `elementor/assets/js/` i `elementor-pro/assets/js/`, o falla l'autoplay de vídeos de
  fons i el JS del carret de WooCommerce.
- **WP Fastest Cache + Polylang Pro**: incompatibles per servir caché — el redirect 302
  de Polylang Pro s'executa abans que es serveixi la caché. Cal caché a nivell de
  Nginx (via Plesk).
- **Elementor Atomic Editor**: el nou sistema de design tokens (CSS variables) no és
  compatible amb els Globals antics.
- **WAVE (accessibilitat)**: els alerts sobre SVG amb `aria-hidden` intencionat no són
  errors reals; WAVE no pot avaluar contrast en pseudo-elements CSS.

## 8. Convencions de codi d'aquest repositori

- PHP amb WordPress Coding Standards: `snake_case` per a funcions/variables, prefixos
  `FISB_` per a classes i `fisb_` per a hooks/opcions, per evitar col·lisions.
- Tot text visible passa per `esc_html__()` / `esc_attr__()` / `__()` amb text domain
  `fisb`, encara que de moment no hi hagi fitxers `.pot` generats.
- Totes les entrades de `$_POST`/`$_REQUEST` es sanititzen abans d'usar-se
  (`sanitize_text_field`, `intval`, `sanitize_key`).
- Totes les accions AJAX validen nonce (`check_ajax_referer`) i capacitat
  (`current_user_can( 'upload_files' )` o `manage_options` per a configuració).
- Comentaris i missatges d'interfície en **català**, seguint la resta de projectes de
  Finismedia.

## 9. Properes tasques previstes (backlog)

- [ ] Meta_query real per a filtres d'estat amb paginació exacta.
- [ ] Exportació CSV de l'estat ALT/Títol per adjuntar a informes d'auditoria SEO.
- [ ] Integració amb la metodologia de la secció 6: botó "Genera informe d'imatges"
      que apliqui els nivells de prioritat (Crític/Alt/Mitjà/Baix) sobre les imatges
      detectades sense ALT.
- [ ] Tests unitaris per `FISB_Generator` (WP_Mock o similar).
- [ ] Validar amb `php -l` / PHPCS (WordPress-Extra ruleset) en un entorn amb PHP
      disponible.
