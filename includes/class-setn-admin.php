<?php
/**
 * Admin tab: options and tools for WordPress admin (Settinator).
 *
 * @package Settinator
 * @author Castellón
 * @copyright 2026 Castellón
 * @version 1.0.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Setn_Admin
 */
class Setn_Admin {

	/**
	 * Tab slug for the Admin tab.
	 *
	 * @var string
	 */
	const TAB_SLUG = 'admin';

	/**
	 * Option name for the custom admin URL slug.
	 *
	 * @var string
	 */
	const OPTION_ADMIN_SLUG = 'setn_admin_slug';

	/**
	 * Nonce action for the admin slug form.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'setn_save_admin_slug';

	/**
	 * Reserved slugs that cannot be used as custom admin path.
	 *
	 * @var string[]
	 */
	const RESERVED_SLUGS = array( 'wp-admin', 'wp-login', 'wp-login.php', 'wp-content', 'wp-includes', 'admin', 'feed', 'index.php', 'settinator' );

	/**
	 * Get the current custom admin slug (empty string = use default wp-admin).
	 *
	 * @return string
	 */
	public static function get_admin_slug() {
		$slug = get_option( self::OPTION_ADMIN_SLUG, '' );
		return is_string( $slug ) ? $slug : '';
	}

	/**
	 * Validate and sanitize a candidate admin slug.
	 *
	 * @param string $slug Raw input.
	 * @return array{ ok: bool, slug: string, error: string } ok and slug if valid, error message otherwise.
	 */
	public static function validate_admin_slug( $slug ) {
		$slug = is_string( $slug ) ? trim( $slug ) : '';
		if ( '' === $slug ) {
			return array( 'ok' => true, 'slug' => '', 'error' => '' );
		}
		$slug = strtolower( $slug );
		if ( ! preg_match( '/^[a-z0-9_-]+$/', $slug ) ) {
			return array(
				'ok'    => false,
				'slug'  => '',
				'error' => __( 'Solo letras, números, guiones y guiones bajos.', 'settinator' ),
			);
		}
		if ( in_array( $slug, self::RESERVED_SLUGS, true ) ) {
			return array(
				'ok'    => false,
				'slug'  => '',
				'error' => __( 'Ese slug está reservado. Elige otro.', 'settinator' ),
			);
		}
		return array( 'ok' => true, 'slug' => $slug, 'error' => '' );
	}

	/**
	 * Render the Admin tab content.
	 *
	 * @return void
	 */
	public static function render_tab() {
		$current_slug = self::get_admin_slug();
		$htaccess_ok  = Setn_Htaccess::is_writable();
		?>
		<p class="description">
			<?php esc_html_e( 'Opciones y herramientas para el escritorio de WordPress.', 'settinator' ); ?>
		</p>

		<div class="card" style="max-width: 640px; padding: 20px; margin-bottom: 20px;">
			<h2 class="title" style="margin-top: 0;"><?php esc_html_e( 'URL del escritorio (wp-admin)', 'settinator' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Puedes cambiar la ruta de acceso al escritorio para que no sea /wp-admin/. Por ejemplo, si pones "mi-panel", el escritorio estará en /mi-panel/ y /wp-admin/ redirigirá allí.', 'settinator' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . Setn_Settings::PAGE_SLUG . '&tab=' . self::TAB_SLUG ) ); ?>" id="setn-admin-slug-form">
				<?php wp_nonce_field( self::NONCE_ACTION, 'setn_admin_slug_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="setn_admin_slug"><?php esc_html_e( 'Ruta del escritorio', 'settinator' ); ?></label>
						</th>
						<td>
							<input type="text" name="setn_admin_slug" id="setn_admin_slug" value="<?php echo esc_attr( $current_slug ); ?>"
								class="regular-text" placeholder="wp-admin" <?php echo $htaccess_ok ? '' : 'readonly'; ?>>
							<p class="description">
								<?php esc_html_e( 'Deja en blanco para usar la ruta por defecto (wp-admin). Solo letras, números, guiones y guiones bajos.', 'settinator' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php if ( $htaccess_ok ) : ?>
					<p>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Guardar', 'settinator' ); ?></button>
					</p>
				<?php else : ?>
					<p class="description" style="color: #b32d2e;">
						<?php esc_html_e( 'No se puede escribir en .htaccess. Ajusta los permisos del archivo o del directorio raíz para poder cambiar esta opción.', 'settinator' ); ?>
					</p>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}
}
