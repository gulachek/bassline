#!/usr/bin/env python3

# Verify login makes you be the user you said you were

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

import sys
import argparse

parser = argparse.ArgumentParser(
        prog = 'Login',
        description = 'Test logging in'
        )

parser.add_argument('uri')

args = parser.parse_args()

driver = webdriver.Chrome()
driver.get(f"{args.uri}/login/")

# Select proper tab
s = 'document.querySelector(".tab-strip").activateTab("noauth");'
driver.execute_script(s)

# Submit user info
tabItem = driver.find_element(By.CSS_SELECTOR, 'tab-item[key="noauth"]')

select = Select(tabItem.find_element(By.TAG_NAME, 'select'))
select.select_by_visible_text('gulachek')
tabItem.find_element(By.CSS_SELECTOR, 'input[type="submit"]').click()

# Verify user
uname = driver.find_element(By.CLASS_NAME, 'username')
assert uname.text == 'gulachek'
