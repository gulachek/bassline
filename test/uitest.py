#!/usr/bin/env python3

import unittest

from uilib.site import Site

from selenium import webdriver
from selenium.webdriver.common.by import By

import os
import sys

if 'TEST_BASE_URI' not in os.environ:
    print('Must specify TEST_BASE_URI environment variable')
    sys.exit(1)

uri = os.environ['TEST_BASE_URI']

class TestLogin(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls._driver = webdriver.Chrome()

    @classmethod
    def tearDownClass(cls):
        cls._driver.close()

    def setUp(self):
        self.site = Site(uri, TestLogin._driver)
        self.site.logOut()

    def test_username_matches_user(self):
        self.site.logInAsUser('gulachek')
        self.assertEqual(self.site.currentUsername(), 'gulachek')

    def test_logout_eliminates_user(self):
        self.site.logInAsUser('gulachek')
        self.site.logOut()
        self.assertIsNone(self.site.currentUsername())

if __name__ == '__main__':
    unittest.main()
