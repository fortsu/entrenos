#!/bin/sh
if [ -z "$1" ]; then
	echo "Dry run mode, no changes will be made"
    force="no"
else
	force=$1
fi

# Default values
options="-anCvz"
if [ $force == "yes" ] ; then
    options="-aCvz"
fi
port="22"
base_path="/var/www/html/entrenos/"
remote_path="/var/www/entrenos/"
exclude_file="${base_path}exclude-entrenos.txt"
username="david"
server="entrenos.fortsu.com"

# Sync data
rsync $options --rsh="ssh -p ${port}" --checksum --exclude-from=$exclude_file $base_path $username@$server:$remote_path
