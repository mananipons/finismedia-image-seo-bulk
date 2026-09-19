<div align="center">

# Finismedia Image SEO Bulk Editor

**Edita en lot l'ALT i el Títol de les imatges de la mediateca de WordPress, amb generació automàtica i sincronització configurable amb WPML.**

![Versió](https://img.shields.io/badge/versi%C3%B3-1.1.1-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b?logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4?logo=php&logoColor=white)
![WPML](https://img.shields.io/badge/WPML-opcional-orange)
![Llicència](https://img.shields.io/badge/llic%C3%A8ncia-GPL%20v2%2B-green)

</div>

---

## Què és

Un plugin de WordPress creat per [Finismedia](https://finismedia.com) per accelerar la correcció SEO d'imatges en projectes de client. Afegeix una pantalla sota **Mèdia → SEO Imatges** amb una taula de tota la mediateca on es pot veure, filtrar i editar l'**ALT** i el **Títol** de cada imatge, i on es pot generar aquest text automàticament, imatge per imatge o en lot.

Resol tres problemes recurrents que apareixen a les auditories SEO:

- Imatges **sense ALT**.
- Imatges amb el **Títol buit** (o amb el nom de fitxer brut de la càmera).
- **Inconsistència entre idiomes** en webs amb WPML.

> No crea cap taula nova a la base de dades. Llegeix i escriu directament les dades natives de WordPress: el meta `_wp_attachment_image_alt` (ALT) i el camp `post_title` de l'adjunt (Títol).

## Característiques

| | |
|---|---|
| **Taula d'edició inline** | Una fila per imatge amb miniatura, fitxer, ALT, Títol, on s'utilitza i estat. Els camps es desen sols en canviar-los (AJAX). |
| **Filtres** | Per estat (falta ALT / falta Títol / falta qualsevol dels dos), per idioma (si hi ha WPML) i cerca de text. |
| **Generació automàtica** | Tres mètodes: nom de fitxer, context del post/pàgina i plantilla amb placeholders. Disponible per a ALT i per a Títol. |
| **Accions en lot** | Selecciona imatges i genera o buida ALT/Títol de cop. |
| **Detecció d'ús** | Mostra a quin post/pàgina s'utilitza cada imatge (com a destacada o dins del contingut). |
| **WPML opcional** | Tres modes de sincronització entre traduccions, triats per l'administrador. Sense WPML, el plugin funciona igual. |
| **Lleuger** | Sense taules noves, sense migracions, sense dependències externes. |

## Ubicació al tauler

| Pàgina | Ruta | Permís necessari |
|---|---|---|
| SEO d'imatges en lot | **Mèdia → SEO Imatges** (`upload.php?page=fisb-images`) | `upload_files` |
| Configuració | **Mèdia → SEO Imatges - Config** (`upload.php?page=fisb-settings`) | `manage_options` |

## Com funciona

### 1. La taula d'imatges

Llista totes les imatges de la mediateca (adjunts amb MIME `image`), de més recent a més antiga, **50 per pàgina**.

| Columna | Contingut |
|---|---|
| ☐ | Casella per seleccionar l'imatge per a accions en lot |
| Miniatura | Previsualització de 50×50 px |
| Fitxer | Nom del fitxer (enllaç a l'edició de l'adjunt) i enllaços de generació ràpida |
| Text ALT | Camp editable |
| Títol | Camp editable |
| S'utilitza a | Post/pàgina on apareix la imatge, o «No detectat» |
| Estat | Insígnia d'estat (veure sota) |
| Idioma | Codi d'idioma de la imatge (només si WPML és actiu) |

**Insígnies d'estat**

| Insígnia | Significat |
|---|---|
| 🔴 Falta ALT i Títol | Els dos camps són buits |
| 🟠 Falta ALT | Té Títol però no ALT |
| 🟡 Falta Títol | Té ALT però no Títol |
| 🟢 OK | Tots dos camps omplerts |

### 2. Desat automàtic

Quan canvies el valor d'un camp i el deixes (esdeveniment `change`), el plugin el desa per AJAX amb un `nonce` i comprovació de permisos. Mentre es desa, el camp queda bloquejat; quan acaba, es marca breument com a desat. Si hi ha un error, es mostra una alerta.

> El valor es neteja amb `sanitize_text_field()`: l'ALT i el Títol són **text pla d'una sola línia**, sense HTML ni salts de línia.

### 3. Generació automàtica de text

Hi ha tres mètodes. Tots retornen un text net; el plugin decideix si es desa com a ALT o com a Títol segons on l'apliquis.

#### Per nom de fitxer

Neteja el nom del fitxer i el converteix en text llegible:

1. Treu l'extensió.
2. Elimina el sufix de mida que WordPress afegeix (`-300x200`).
3. Elimina identificadors numèrics llargs al final (`-20240315`, 4 o més dígits).
4. Canvia `-` i `_` per espais i col·lapsa espais repetits.
5. Posa la primera lletra en majúscula.

| Fitxer | Resultat |
|---|---|
| `vestit-blau-300x200.jpg` | `Vestit blau` |
| `casa_rural-20240315.webp` | `Casa rural` |

#### Per context

Fa servir el **títol del post/pàgina** on s'utilitza la imatge. Ordre de cerca:

1. El post pare de l'adjunt (`post_parent`).
2. Un post que la tingui com a **imatge destacada** (`_thumbnail_id`).
3. Un post **publicat** que la referenciï al contingut (classe `wp-image-{ID}`).

Si no troba cap context, **recorre al nom de fitxer** perquè el camp no es quedi buit. El títol es llegeix cru de la base de dades (`post_title`), sense filtres de presentació, de manera que no es desen entitats HTML (`&#8217;`) ni cometes tipogràfiques.

#### Per plantilla

Aplica un patró propi amb aquests placeholders:

| Placeholder | Valor |
|---|---|
| `{filename}` | Text derivat del nom de fitxer (com al mètode 1) |
| `{post_title}` | Títol del post/pàgina de context (com al mètode 2, buit si no n'hi ha) |
| `{site_name}` | Nom del lloc (Ajustos → Generals) |

Després d'aplicar-la, es col·lapsen els espais i es retallen separadors (` - – |`) que hagin quedat als extrems si algun placeholder era buit.

**Exemple:** plantilla `{post_title} - {filename}`, imatge `sala-estar.jpg` usada a la pàgina «Casa rural a Cardedeu» → `Casa rural a Cardedeu - Sala estar`.

Si la plantilla és buida, s'usa `{post_title}`.

### 4. On es pot generar

**Per fila** — sota el nom del fitxer, enllaços d'un clic:

- `ALT: genera per nom | genera per context`
- `Títol: genera per nom | genera per context`

El text generat s'omple al camp i **es desa immediatament**.

**En lot** — selecciona imatges, tria una acció i aplica-la:

| Acció | Efecte |
|---|---|
| Generar ALT: a partir del nom de fitxer | ALT ← nom de fitxer |
| Generar ALT: a partir del context | ALT ← context |
| Generar ALT: aplicar plantilla | ALT ← plantilla |
| Generar Títol: a partir del nom de fitxer | Títol ← nom de fitxer |
| Generar Títol: a partir del context | Títol ← context |
| Generar Títol: aplicar plantilla | Títol ← plantilla |
| Buidar ALT | ALT ← buit |
| Buidar Títol | Títol ← buit |

La plantilla de les accions en lot és el camp de text que hi ha a la barra de filtres de la taula (per defecte, la de la configuració). Les accions en lot **sobreescriuen** el valor existent: filtra primer per «Falta ALT» si només vols omplir buits.

### 5. Compatibilitat amb WPML

WPML és **totalment opcional**. Sense WPML, la columna d'idioma, el filtre d'idioma i l'opció de sincronització no apareixen i el plugin no fa cap crida a WPML.

Amb WPML actiu, l'administrador tria a **Mèdia → SEO Imatges - Config** com es tracten les traduccions d'una imatge:

| Mode | Comportament |
|---|---|
| **Independent per idioma** | No es toca cap traducció. |
| **Sincronitzar sempre** | En desar ALT/Títol, es copien a totes les traduccions de la imatge. |
| **Preguntar en cada desat** *(per defecte)* | Cada desat inline mostra una confirmació abans de sincronitzar. |

> **Important:** la sincronització només té efecte quan WPML té **Media Translation** activada, és a dir, quan cada idioma té el seu propi adjunt. Si els mèdia són compartits entre idiomes, és el mateix element i no cal sincronitzar res.
>
> A les **accions en lot**, la sincronització només s'aplica en mode «Sincronitzar sempre»; en mode «Preguntar» no es sincronitza (no hi ha confirmació per lots).

### 6. Configuració

| Opció | Descripció |
|---|---|
| Mètode de generació per defecte | `filename`, `context` o `template`. Es desa com a valor de referència. |
| Plantilla | Plantilla per defecte amb `{filename}`, `{post_title}`, `{site_name}` (per defecte, `{post_title}`). |
| Sincronització amb WPML | Un dels tres modes anteriors (només visible amb WPML actiu). |

Tot es desa en una única opció de WordPress: `fisb_settings`.

## Instal·lació

1. Descarrega o clona aquest repositori.
2. Copia la carpeta **`plugin/`** a `wp-content/plugins/` i reanomena-la `finismedia-image-seo-bulk`
   (o comprimeix-la en un `.zip` i puja'l des de **Connectors → Afegeix nou → Puja un connector**).
3. Activa **Finismedia Image SEO Bulk Editor** als connectors.
4. Ves a **Mèdia → SEO Imatges**.

> Tots els fitxers del plugin viuen dins de `plugin/`, no a l'arrel del repositori.

### Requisits

- WordPress 5.8 o superior
- PHP 7.4 o superior
- WPML (opcional)

## Estructura del repositori

```
finismedia-image-seo-bulk/
├── README.md
├── AGENTS.md                            # Context per a agents LLM (decisions de disseny, deute tècnic)
└── plugin/                              # El plugin de WordPress
    ├── finismedia-image-seo-bulk.php    # Capçalera del plugin, includes i arrencada
    ├── readme.txt                       # Readme en format estàndard de WordPress
    ├── includes/
    │   ├── class-fisb-generator.php     # Generació de text (nom / context / plantilla)
    │   ├── class-fisb-wpml.php          # Única capa que parla amb l'API de WPML
    │   ├── class-fisb-settings.php      # Settings API (opció fisb_settings)
    │   ├── class-fisb-list-table.php    # WP_List_Table: taula, filtres i accions en lot
    │   ├── class-fisb-admin.php         # Menús, pàgines i càrrega d'assets
    │   └── class-fisb-ajax.php          # Endpoints AJAX
    └── assets/
        ├── admin.js                     # Desat inline i generació per fila
        └── admin.css                    # Insígnies d'estat i estils
```

## Notes tècniques

**Seguretat**

- Els endpoints AJAX (`fisb_save_field`, `fisb_generate`) validen `nonce` (`check_ajax_referer`) i la capacitat `upload_files`.
- Les accions en lot validen el `nonce` de `WP_List_Table` (`check_admin_referer`).
- Les entrades de `$_POST`/`$_REQUEST` es sanititzen abans d'usar-se.
- La configuració requereix `manage_options`.

**Dades**

| Camp | On viu |
|---|---|
| ALT | Postmeta `_wp_attachment_image_alt` de l'adjunt |
| Títol | `post_title` de l'adjunt |
| Configuració | Opció `fisb_settings` |

**Endpoints AJAX**

| Acció | Paràmetres | Resposta |
|---|---|---|
| `fisb_save_field` | `id`, `field` (`alt`\|`title`), `value`, `sync` | `{ message, synced }` |
| `fisb_generate` | `id`, `method` (`filename`\|`context`\|`template`) | `{ value }` (no desa; el JS ho desa després) |

**Aïllament de WPML.** Totes les crides a l'API de WPML viuen a `FISB_WPML`. Cap altra classe les fa directament, i totes es protegeixen amb `FISB_WPML::is_active()`.

## Limitacions conegudes

- **Paginació inexacta amb filtres actius.** Els filtres d'estat i d'idioma s'apliquen després de la consulta, sobre les 50 imatges de la pàgina actual. Amb un filtre actiu, una pàgina pot mostrar menys de 50 files i el recompte total reflecteix la mediateca sense filtrar. En mediateques grans (> 500 imatges) cal migrar el filtre a un `meta_query` real.
- **La detecció de context és una heurística.** Cerca l'imatge destacada i la classe `wp-image-{ID}` al contingut (consulta SQL directa); no escaneja blocs, constructors com Elementor ni camps personalitzats.
- **ALT d'una sola línia i sense HTML** (per `sanitize_text_field`).
- **El «Mètode de generació per defecte» de la configuració es desa, però de moment no governa cap acció.** Les accions i enllaços de generació trien el mètode explícitament.
- **No hi ha tests automatitzats.**

## Compatibilitat

Pensat per a l'ecosistema habitual de Finismedia: WordPress + Elementor + WooCommerce, amb WPML com a opció (Polylang i Weglot no s'integren: en aquests casos el plugin funciona com a editor sense sincronització entre idiomes).

## Registre de canvis

| Versió | Canvis |
|---|---|
| **1.1.1** | Fix: la generació per context desava el títol ja «texturitzat» (`&#8217;`, cometes «»). Ara es fa servir el `post_title` cru. |
| **1.1.0** | Generació automàtica de Títol (nom / context / plantilla), per fila i en lot. Abans només existia per a l'ALT. |
| **1.0.0** | Versió inicial. |

## Llicència

GPL v2 o posterior.

---

<div align="center">

Fet per [Finismedia](https://finismedia.com) · Desenvolupament web i digitalització al Vallès Oriental

</div>
