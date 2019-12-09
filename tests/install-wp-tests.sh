#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [no-cache]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
NO_CACHE=${6-false}
TRAVIS=${$TRAVIS:-false}

EXEC_DIR="$(pwd)"

# Formatting output to make it easier to function read 
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

set -ex

update_postmedia_test_config() {

	# pull down the custom files required to support wordpress and the testing configs
	cd $EXEC_DIR
	git clone --quiet https://github.com/Postmedia-Digital/CI_Config.git /tmp/ci_config

	# Load the configuraiton files and get dependency versions
	source /tmp/ci_config/versions.cfg
	echo -e "${CYAN}Executing with WordPress ${WP_VERSION}, PHP Codesniffer ${PHP_CODESNIFFER_VERSION}, WordPress Coding Standards ${WP_CODING_STD_VERSION}, ESS Lint ${JS_LINT_VERSION}, and CSS Lint ${CSS_LINT_VERSION}${NC}"

}

remove_previous_temp_files() {
	# this function removes and reverts files before the installations, no errors are passed on if they don't exist
	# the deletion of wordpress, phpcodesniffer and related installations are handled based on cache status in those functions
	rm -rf /tmp/ci_config
}

remove_previous_temp_files
update_postmedia_test_config

# Export these into environment variables as other parts may rely on them
export WP_TESTS_DIR=${WP_TESTS_DIR:-${INSTALL_PATH}/wordpress-tests-lib/}
export WP_CORE_DIR="$INSTALL_PATH/wordpress/"
export CODE_SNIFFER_DIR="$INSTALL_PATH/php-codesniffer/"
export WP_CODING_STD_DIR="$INSTALL_PATH/wordpress-coding-standards/"

install_wp() {
	if [ "$NO_CACHE" == "false" ] && [ -d $WP_CORE_DIR ]; then
		# Directory exists, lets look for the version
		if [ -f $WP_CORE_DIR/.ci-$WP_VERSION.ver ]; then
			# current version in cache or installed is correct, skip
			echo -e "${CYAN}WordPress version ${WP_VERSION} is cached, skipping re-install.${NC}"
			return
		else
			# Not found or installed.
			echo -e "${RED}Installed WordPress version does not match version required ( ${WP_VERSION} ), beginning fresh install...${NC}"
		fi
	else
		# either not caching or directory doesnt exist
		echo -e "${RED}No cached copy present or requested for WordPress, beginning fresh install...${NC}"
	fi

	# Remove any files that may still be there before re-installing
	if [ -d $WP_CORE_DIR ]; then
		rm -rf $WP_CORE_DIR
	fi

	mkdir -p $WP_CORE_DIR

	local ARCHIVE_NAME="wordpress-$WP_VERSION"
	wget -nv -O /tmp/wordpress.tar.gz https://wordpress.org/${ARCHIVE_NAME}.tar.gz
	tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR

	# mark the version
	touch ${WP_CORE_DIR}/.ci-${WP_VERSION}.ver
	cp /tmp/ci_config/db.php $WP_CORE_DIR/wp-content/db.php 
}

install_test_suite() {
	if [ "$NO_CACHE" == "false" ] && [ -d $WP_TESTS_DIR ]; then
		# Directory exists, and we are using cache if version is correct
		if [ -f $WP_TESTS_DIR/.ci-$WP_VERSION.ver ]; then
			# current version is in cach/installed.  Skip.
			echo -e "${CYAN}WordPress Test Librairies version ${WP_VERSION} is cached, skipping re-install.${NC}"
			return
		else
			# Not installed or incorrect version
			echo -e "${RED}Installed WordPress Test Librairies version does not match the version required ( ${WP_VERSION} ), beginning fresh install...${NC}"
		fi
	else
		# either we are not caching or directory does not exist ( first run )
		echo -e "${RED}No cached copy present or requested for WordPress Test Library, beginning fresh install...${NC}"
	fi

	# Remove any files that may still be there before re-installing
	if [ -d $WP_TESTS_DIR ]; then
		rm -rf $WP_TESTS_DIR
	fi

	# set up testing suite
	mkdir -p $WP_TESTS_DIR
	cd $WP_TESTS_DIR
	svn co --quiet --non-interactive --trust-server-cert https://develop.svn.wordpress.org/tags/${WP_VERSION}/tests/phpunit/includes/ 
	svn co --quiet --non-interactive --trust-server-cert https://develop.svn.wordpress.org/tags/${WP_VERSION}/tests/phpunit/tests/
	svn co --quiet --non-interactive --trust-server-cert https://develop.svn.wordpress.org/tags/${WP_VERSION}/tests/phpunit/data/

	wget -nv -O wp-tests-config.php https://develop.svn.wordpress.org/tags/${WP_VERSION}/wp-tests-config-sample.php

	# Pull down wpcom_vip helper functionality - Developers should include these in the bootstrap file as needed only
	svn co --quiet --non-interactive --trust-server-cert https://vip-svn.wordpress.com/plugins/vip-do-not-include-on-wpcom
	wget -nv https://vip-svn.wordpress.com/plugins/vip-init.php
	wget -nv https://vip-svn.wordpress.com/plugins/vip-helper.php
	wget -nv https://vip-svn.wordpress.com/plugins/vip-helper-wpcom.php
	wget -nv https://vip-svn.wordpress.com/plugins/vip-helper-stats-wpcom.php

	# mark the version
	touch ${WP_TESTS_DIR}/.ci-${WP_VERSION}.ver
}

update_test_configuration_files() {
	
	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi
	
	# Remove the temp files for test configuration
	rm -f $EXEC_DIR/tests/phpunit.xml
	rm -f $EXEC_DIR/tests/phpunit.xml.bak
	rm -f $EXEC_DIR/tests/codesniffer.ruleset.xml

	# copy the codesniffer ruleset into the tests folder.
	cp /tmp/ci_config/codesniffer.ruleset.xml $EXEC_DIR/tests/

	# copy the phpunit test config into the tests folder.
	cp /tmp/ci_config/phpunit.xml $EXEC_DIR/tests/

	# modifies the wp-tests-config.php file for db access
	sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" $WP_TESTS_DIR/wp-tests-config.php
	sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" $WP_TESTS_DIR/wp-tests-config.php
	sed $ioption "s/yourusernamehere/$DB_USER/" $WP_TESTS_DIR/wp-tests-config.php
	sed $ioption "s/yourpasswordhere/$DB_PASS/" $WP_TESTS_DIR/wp-tests-config.php
	sed $ioption "s|localhost|${DB_HOST}|" $WP_TESTS_DIR/wp-tests-config.php

	# modify the path to reflect actual test folder path
	sed $ioption "s:replace/:$WP_TESTS_DIR/tests/:" $EXEC_DIR/tests/phpunit.xml

	# update the path in bootstrap.php for the WP_TESTS_DIR variable.
	sed $ioption "s:_tests_dir = '[a-zA-Z0-9\-\/\_]*';:_tests_dir = '$WP_TESTS_DIR';:" $EXEC_DIR/tests/bootstrap.php
	cp $EXEC_DIR/tests/phpunit.xml /tmp/phpunit.xml
}

install_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [[ "$DB_SOCK_OR_PORT" =~ ^[0-9]+$ ]] ; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# Yes we know using a password on a command line can be insecure.  Since these are databases with no data worth anything that exist solely for 
	# the test execution then drop out of existence, we will forgive this. Risk is a window of 2 minutes for someone to take over the 
	# virtual machine, grep logs and exploit the system, but it is also Travis-ci's problem as they use blank passwords anyway.
	#
	# drop database if it exists (-f forces so no prompt to confirm, and ignores error if db didnt exist)
	#mysqladmin drop $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA -f
	mysql -u "$DB_USER" --password="$DB_PASS" -e "drop database if exists $DB_NAME"$EXTRA

	# create database
	mysqladmin create $DB_NAME --user="$DB_USER" --password="$DB_PASS"$EXTRA
}

install_coding_standards() {

	if [ "$NO_CACHE" == "false" ] && [ -d $WP_CODING_STD_DIR ]; then
		# Directory exists, and we are using cache if version is correct
		if [ -f $WP_CODING_STD_DIR/.ci-$WP_CODING_STD_VERSION.ver ]; then
			# current version is in cach/installed.  Skip.
			echo -e "${CYAN}WordPress Coding Standards version ${WP_CODING_STD_VERSION} is cached, skipping re-install.${NC}"
			return
		else
			# Not installed or incorrect version
			echo -e "${RED}Installed WordPress Coding Standards version does not match the version required ( ${WP_CODING_STD_VERSION} ), beginning fresh install...${NC}"
		fi
	else
		# either we are not caching or directory does not exist ( first run )
		echo -e "${RED}No cached copy present or requested for WordPress Coding Standards, beginning fresh install...${NC}"
	fi

	# Remove any files that may still be there before re-installing
	if [ -d $WP_CODING_STD_DIR ]; then
		rm -rf $WP_CODING_STD_DIR
	fi

	# Install WordPress Coding Standards.
	git clone --quiet https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards.git $WP_CODING_STD_DIR

	# Hop into the WordPress Coding Standards directory.
	cd $WP_CODING_STD_DIR

	# Point to the correct tag for the verion we accept
	git checkout tags/$WP_CODING_STD_VERSION -b wp_coding_std_version

	# Remove the .git folder so that we only cache the files needed.
	rm -rf ./.git

	# mark the version
	touch ${WP_CODING_STD_DIR}/.ci-${WP_CODING_STD_VERSION}.ver
}

install_code_sniffer() {

	if [ "$NO_CACHE" == "false" ] && [ -d $CODE_SNIFFER_DIR ]; then
		# Directory exists, and we are using cache if version is correct
		if [ -f $CODE_SNIFFER_DIR/.ci-$PHP_CODESNIFFER_VERSION.ver ]; then
			# current version is in cach/installed.  Skip.
			echo -e "${CYAN}PHP Codesniffer version ${PHP_CODESNIFFER_VERSION} is cached, skipping re-install.${NC}"
			# Set install path for WordPress Coding Standards
			cd $CODE_SNIFFER_DIR
			./scripts/phpcs --config-set installed_paths ${WP_CODING_STD_DIR}
			return
		else
			# Not installed or incorrect version
			echo -e "${RED}Installed PHP Codesniffer version does not match the version required ( ${PHP_CODESNIFFER_VERSION} ), beginning fresh install...${NC}"
		fi
	else
		# either we are not caching or directory does not exist ( first run )
		echo -e "${RED}No cached copy present or requested for PHP Codesniffer, beginning fresh install...${NC}"
	fi

	# Remove any files that may still be there before re-installing
	if [ -d $CODE_SNIFFER_DIR ]; then
		rm -rf $CODE_SNIFFER_DIR
	fi

	# Install CodeSniffer for WordPress Coding Standards checks.
	git clone --quiet https://github.com/squizlabs/PHP_CodeSniffer.git $CODE_SNIFFER_DIR

	# Hop into CodeSniffer directory.
	cd $CODE_SNIFFER_DIR

	# Point to the correct tag for the version we accept
	git checkout tags/$PHP_CODESNIFFER_VERSION -b php_codesniffer_version

	# Remove the .git folder so that we only cache the files needed
	rm -rf ./.git
	
	# Set install path for WordPress Coding Standards
	./scripts/phpcs --config-set installed_paths ${WP_CODING_STD_DIR}

	# Return to build directory and rehash env vars
	cd $EXEC_DIR

	# Testing removal, as not used on most systems now, may add a check for travis environment variable to choose to run this.
	#phpenv rehash
	
	# mark the version
	touch ${CODE_SNIFFER_DIR}/.ci-${PHP_CODESNIFFER_VERSION}.ver
}

install_lints() {
	# First we copy the package.json from /tmp/ci_config into the $EXEC_DIR
	cp /tmp/ci_config/package.json $EXEC_DIR

	# Ensure we are in the right directory to read package.json
	cd $EXEC_DIR

	# Install dependencies
	npm install

	# Copy the CSS Lint Tool (csslint) config
	cp /tmp/ci_config/.csslintrc $EXEC_DIR

	# Copy the javascript Lint Tool (eslint) config
	cp /tmp/ci_config/.eslintrc $EXEC_DIR
}

create_ci_build_path() {
	mkdir -p $INSTALL_PATH
	# Ensure caching processes can access this path with any service account, must have rwx so full 777 for this.
	chmod 777 $INSTALL_PATH
}

create_ci_build_path
install_wp
install_test_suite
install_db
install_coding_standards
install_code_sniffer
install_lints
update_test_configuration_files
