<?php
/**
 * PRC Collections Activator
 *
 * @package PRC\Platform\Collections
 */

namespace PRC\Platform\Collections;

use DEFAULT_TECHNICAL_CONTACT;

/**
 * PRC Collections Activator
 *
 * @package PRC\Platform\Collections
 */
class PRC_Collections_Activator {

	public static function activate() {
		flush_rewrite_rules();

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Collections Activated',
			'The PRC Collections plugin has been activated on ' . get_site_url()
		);
	}
}
