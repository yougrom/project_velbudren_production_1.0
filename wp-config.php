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
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'lolshowr_velbdr');

/** MySQL database username */
define('DB_USER', 'lolshowr_velbdr');

/** MySQL database password */
define('DB_PASSWORD', '4724cmpw');

/** MySQL hostname */
define('DB_HOST', 'lolshowr.mysql.ukraine.com.ua');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'q)eiRYKZaHQwLu7XuoM8P^3mvxLf#CWi5GMHJ*RMTf!elR6p*5)itYCmJbp#PkH0');
define('SECURE_AUTH_KEY',  '#9vtjIYclJR7qJZDk!&5HLwGjZD*GlxqYYyGMj@DTmkCLNoVrBgB14VEiVMxS2aA');
define('LOGGED_IN_KEY',    'JC&hPcYnleLeNx8SB9k2OHGm249&b9wABO6HiiGOEI(8%iiWJ&mv&ZQ1qr54QBzG');
define('NONCE_KEY',        'OZv0^SjIiIm%oX#R*Kw4&rEDZcBqq0EnS@KlGUD2Tt*KvHb1*swme*XNS*6dGmUq');
define('AUTH_SALT',        'lz0qtrYLtv@wpFfS1XS8vSi0F91fqZOvEUdckNBhodvh5!OYrjJmzjjrz@toW7eI');
define('SECURE_AUTH_SALT', 'gQ5DS7qV7lERoZiOvnn3N&Phv5hR1lt16&xU9MI6YFXl1p1EdO1uQ3@sQ1gOos)v');
define('LOGGED_IN_SALT',   'vQjOuymmoFOO54M!Eh^N1!xwaMTVQHh^%fEfR^zq*WzfVZ9%1M@QLlITQzXkJ)UR');
define('NONCE_SALT',       'zbfmS)EgVuloW6JoAx!TmyaOn&Z(e4J0bgrSlvZJhbZD6hjAuc6Ixaiev1frqP0F');
/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

define( 'WP_ALLOW_MULTISITE', true );

define ('FS_METHOD', 'direct');
