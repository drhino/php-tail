#!/bin/bash

./vendor/bin/psalm --show-info=true

./vendor/bin/phpunit tests
