=== Finismedia Image SEO Bulk Editor ===
Contributors: finismedia
Tags: seo, images, alt text, wpml, bulk edit
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later

Edita en lot els atributs ALT i Títol de les imatges de la mediateca, amb generació automàtica opcional i compatibilitat configurable amb WPML.

== Descripció ==

Ubicació: Mèdia > SEO Imatges.

Funcionalitats:

* Llistat de totes les imatges de la mediateca amb edició inline d'ALT i Títol (desat automàtic via AJAX en perdre el focus).
* Filtre per tipus de contingut (pàgines, entrades, productes...): només les imatges usades en aquell tipus, com a fill, destacada o dins del contingut.
* Filtres per estat (falta ALT / falta Títol / falta qualsevol) i per idioma (si WPML està actiu).
* Generació automàtica per fila o en lot:
  - A partir del nom de fitxer (neteja guions/underscores i sufixos de mida).
  - A partir del context (títol del post/pàgina on s'usa la imatge, com a destacada o dins del contingut).
  - Mitjançant una plantilla configurable amb els placeholders {filename}, {post_title}, {site_name}.
* Botó de copiar a cada camp (ALT i Títol) per copiar-hi el contingut de l'altre i desar-lo.
* Accions en lot: generar ALT (3 mètodes), generar Títol (3 mètodes), buidar ALT, buidar Títol.
* Compatibilitat amb WPML configurable a Mèdia > SEO Imatges - Config:
  - Independent per idioma (no toca les traduccions).
  - Sincronitzar sempre amb totes les traduccions de la imatge.
  - Preguntar en cada desat (mostra una confirmació abans de sincronitzar).
  - Nota: la sincronització només té efecte quan WPML té "Media Translation" activada i, per tant,
    existeixen adjunts traduïts com a posts separats; si els mèdia són compartits entre idiomes,
    no cal sincronitzar res perquè ja és el mateix element.

== Notes tècniques ==

* No crea taules noves a la base de dades: fa servir el meta `_wp_attachment_image_alt` i el camp `post_title` natiu dels adjunts.
* La detecció de "context" cerca primer si la imatge és imatge destacada d'algun post, i si no, si apareix referenciada al contingut (classe `wp-image-{ID}`).
* Quan els filtres d'estat/idioma estan actius, el comptador de paginació es basa en el total sense filtrar (limitació coneguda de la v1). En mediateques molt grans es recomana ampliar `FISB_List_Table::prepare_items()` amb un `meta_query`/`WHERE` dedicat per a un recompte exacte.
* Requereix la capacitat `upload_files` per editar imatges i `manage_options` per accedir a la configuració.

== Changelog ==

= 1.1.1 =
* Fix: la generació per context (ALT o Títol) desava el títol del post pare ja "texturitzat" (`&#8217;` en lloc d'apòstrof, cometes «» afegides pel filtre `the_title`). Ara s'usa el `post_title` cru.
* Fix: la sincronització WPML també desava el títol texturitzat a les traduccions.

= 1.1.0 =
* Generació automàtica de Títol (nom de fitxer / context / plantilla), tant per fila com en accions en lot. Abans només estava disponible per a l'ALT.

= 1.0.0 =
* Versió inicial.
