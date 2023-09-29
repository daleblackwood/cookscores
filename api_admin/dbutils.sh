#!/bin/bash

run_query() {
    source ./dbconf.ini
    psql -U $BW_USER -d $BW_DB -c "$1"
}