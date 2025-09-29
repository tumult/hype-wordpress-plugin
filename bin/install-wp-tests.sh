#!/bin/bash
set -x

# Based on the install script from https://github.com/wp-cli/wp-cli/blob/main/templates/plugin-tests/bin/install-wp-tests.sh

set -e

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-6.8}

WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-/tmp/wordpress/}

# Download WordPress
if [ ! -d "$WP_CORE_DIR" ]; then
	mkdir -p "$WP_CORE_DIR"
	wget -q -O "$WP_CORE_DIR/wordpress.tar.gz" "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz"
	tar --strip-components=1 -zxmf "$WP_CORE_DIR/wordpress.tar.gz" -C "$WP_CORE_DIR"
fi

# Copy testing library from WordPress core
if [ ! -d "$WP_TESTS_DIR" ]; then
	mkdir -p "$WP_TESTS_DIR"
	cp -R "$WP_CORE_DIR/tests/phpunit/" "$WP_TESTS_DIR"
fi

# Create test config file
if [ ! -f "$WP_CORE_DIR/wp-tests-config.php" ]; then
	cp "$WP_TESTS_DIR/wp-tests-config-sample.php" "$WP_CORE_DIR/wp-tests-config.php"
	sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_CORE_DIR/wp-tests-config.php"
	sed -i "s/yourusernamehere/$DB_USER/" "$WP_CORE_DIR/wp-tests-config.php"
	sed -i "s/yourpasswordhere/$DB_PASS/" "$WP_CORE_DIR/wp-tests-config.php"
	sed -i "s|localhost|$DB_HOST|" "$WP_CORE_DIR/wp-tests-config.php"
fi
