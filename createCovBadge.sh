#!/bin/bash

 php vendor/bin/phpunit --coverage-html public/clover/
 php vendor/bin/phpunit --coverage-clover  public/clover/clover.xml
 vendor/bin/coverage-badge public/clover/clover.xml misc/coverage.svg coverage