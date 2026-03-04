=== Settinator ===
Contributors: alexcm13
Tags: settings, htaccess, wp-config, configuration, editor
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Editor de .htaccess y wp-config.php desde el escritorio de WordPress, con validación de sintaxis antes de guardar.

== Description ==

Settinator añade un menú propio en el escritorio de WordPress desde el que puedes ver y editar dos archivos críticos de tu instalación:

* **Editor .htaccess**: edita el archivo .htaccess de la raíz con validación de sintaxis Apache (bloques equilibrados, RewriteCond, etc.). Si la sintaxis es incorrecta, no se guarda y se muestra "htaccess failed".
* **Editor wp-config.php**: edita wp-config.php con validación de sintaxis PHP (php -l o comprobación básica). En caso de error de sintaxis se muestra "wp-config failed".

En ambas pestañas se muestra la ruta del archivo y la **última modificación**, para comprobar que los cambios se han guardado correctamente.

= Características =

* Menú propio "Settinator" en el escritorio (icono de tuerca).
* Pestaña General (vacía por defecto).
* Editor .htaccess con validación Apache y aviso de última modificación.
* Editor wp-config.php con validación PHP y aviso de última modificación.
* No se guarda si la sintaxis es incorrecta; mensajes de error claros.
* Código organizado en archivos separados (htaccess, wpconfig, settings).

= Requisitos =

* WordPress 5.0 o superior.
* PHP 7.4 o superior.
* Permisos de escritura en la raíz de WordPress para .htaccess y en wp-config.php.

== Installation ==

1. Sube la carpeta `settinator` a `/wp-content/plugins/`.
2. Activa el plugin desde el menú 'Plugins' en WordPress.
3. Entra en **Settinator** en el menú lateral del escritorio.
4. Usa las pestañas "Editor .htaccess" y "Editor wp-config.php" para editar cada archivo.

== Frequently Asked Questions ==

= ¿Puedo romper mi sitio editando estos archivos? =

Sí. Tanto .htaccess como wp-config.php son archivos críticos. Haz siempre una copia de seguridad antes de modificar. El plugin valida la sintaxis antes de guardar para reducir errores, pero no garantiza que la configuración sea correcta o segura.

= ¿Por qué no me deja guardar? =

Si aparece "htaccess failed" o "wp-config failed", la sintaxis del contenido que has pegado o escrito no es válida. Revisa bloques sin cerrar, comillas, paréntesis, etc.

== Screenshots ==

1. Menú Settinator en el escritorio.
2. Pestaña Editor .htaccess con contenido y última modificación.
3. Pestaña Editor wp-config.php.

== Changelog ==

= 1.0.1 =
* Reorganización del código en archivos separados (class-setn-htaccess.php, class-setn-wpconfig.php, class-setn-settings.php).
* Añadidos readme.txt y readme.md.

= 1.0.0 =
* Lanzamiento inicial.
* Editor .htaccess con validación de sintaxis Apache.
* Editor wp-config.php con validación de sintaxis PHP.
* Última modificación del archivo en cada pestaña.
* Menú propio en el escritorio.

== Upgrade Notice ==

= 1.0.1 =
Mejora de estructura de archivos y documentación (readme). Sin cambios de funcionalidad.
