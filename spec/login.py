#!/usr/bin/env python3

# Verify login makes you be the user you said you were

from uilib.site import Site

from selenium import webdriver
from selenium.webdriver.common.by import By

import sys
import argparse

parser = argparse.ArgumentParser(
        prog = 'Login',
        description = 'Test logging in'
        )

parser.add_argument('uri')

args = parser.parse_args()

driver = webdriver.Chrome()
site = Site(args.uri, driver)

site.logInAsUser('gulachek')

# Verify user
assert site.currentUsername() == 'gulachek'
