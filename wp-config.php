<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'sport_shop' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'gl-tQG2cL@.@zE ]4E/u4;=e0$ap^ezEX vK3FCt?UIo0OxMY)O=,q[v4Mr%Z-;q' );
define( 'SECURE_AUTH_KEY',  'i&|aWQbg{Tvt}]b%&_k;gst8^d/GH;c6kC/QPe=In|t}!lean*h,o7Y-s7j3VFR2' );
define( 'LOGGED_IN_KEY',    'jY{VYv*Z.n)5#KwR>Il@g~mE^4:d4;_}){Gpgvf3]Pw&gO~z1%j!naKG%zs~yz>r' );
define( 'NONCE_KEY',        'eZL)Vh3oC)B[~>CR1z*.bZXInVI1O1N1{?}WX*#giXeE{vQ J28<Z4ECRoiWT!y*' );
define( 'AUTH_SALT',        'YDVz5w70@^dqri`_gErOJ+n@4j_b|[or69G!fo>p{Y=%BO6C<pT0_;tjUBLeV&{P' );
define( 'SECURE_AUTH_SALT', 'q*~j=|SNx$1*QK{Ssz8#tYMKG_GCR?Dw4^~xQJ y)4}E_7s.`C9T:0*J)6)H[4J+' );
define( 'LOGGED_IN_SALT',   '0Uk)WA6$KPw&s$A<eTAw)J%_vB[4wk,5ECoA<%>6F>:vDM=b#B*1>@E^Ov];LVkb' );
define( 'NONCE_SALT',       'yhD//0n_GoBC*V>!G!zZi{aadHB v@O/3U8sg}:Pu=PDf.UHP+M1zMlCjH#(=;gq' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
