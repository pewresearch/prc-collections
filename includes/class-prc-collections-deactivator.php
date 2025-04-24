<?php
/**
 * PRC Collections Deactivator
 *
 * @package PRC\Platform\Collections
 */

namespace PRC\Platform\Collections;

use DEFAULT_TECHNICAL_CONTACT;

/**
 * PRC Collections Deactivator
 *
 * @package PRC\Platform\Collections
 */
class PRC_Collections_Deactivator {

	public static function deactivate() {
		flush_rewrite_rules();

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Collections Deactivated',
			'The PRC Collections plugin has been deactivated on ' . get_site_url()
		);
	}
}
