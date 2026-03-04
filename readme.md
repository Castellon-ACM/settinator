# Settinator

**Versión:** 1.0.1  
**Autor:** Castellón  
**Licencia:** GPL-2.0+

Editor de **.htaccess** y **wp-config.php** desde el escritorio de WordPress, con validación de sintaxis antes de guardar.

## Requisitos

- WordPress 5.0+
- PHP 7.4+
- Permisos de escritura en la raíz de WordPress (`.htaccess`) y en `wp-config.php`

## Instalación

1. Sube la carpeta `settinator` a `wp-content/plugins/`.
2. Activa el plugin en **Plugins**.
3. En el menú lateral del escritorio, entra en **Settinator** (icono de tuerca).

## Uso

Settinator añade un menú propio con tres pestañas:

| Pestaña | Descripción |
|--------|-------------|
| **General** | Vacía por defecto. |
| **Editor .htaccess** | Ver y editar el `.htaccess` de la raíz. Se valida la sintaxis Apache; si es incorrecta, no se guarda y se muestra *htaccess failed*. |
| **Editor wp-config.php** | Ver y editar `wp-config.php`. Se valida la sintaxis PHP; si es incorrecta, no se guarda y se muestra *wp-config failed*. |

En cada editor se muestra la **ruta del archivo** y la **última modificación**, para comprobar que los cambios se han guardado.

## Características

- Menú propio «Settinator» en el escritorio.
- Validación de sintaxis antes de guardar (.htaccess: bloques Apache; wp-config: PHP).
- Aviso de última modificación del archivo.
- Código organizado en archivos separados por responsabilidad (`class-setn-htaccess.php`, `class-setn-wpconfig.php`, `class-setn-settings.php`).

## Advertencia

`.htaccess` y `wp-config.php` son archivos críticos. Un error puede dejar el sitio inaccesible. Haz siempre una copia de seguridad antes de modificar.

## Changelog

### 1.0.1
- Reorganización del código en archivos separados.
- Añadidos `readme.txt` y `readme.md`.

### 1.0.0
- Lanzamiento inicial.
- Editor .htaccess con validación Apache.
- Editor wp-config.php con validación PHP.
- Última modificación del archivo en cada pestaña.
- Menú propio en el escritorio.
